<?php

namespace App\Services\Imports;

use App\Models\Country;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserSpreadsheetImporter
{
    public const MAX_ROWS = 1000;

    public const HEADERS = [
        'name',
        'email',
        'role',
        'company',
        'phone',
        'country',
        'region_state',
        'bio',
    ];

    /**
     * @return array{created: int, skipped: int, failed: int, errors: array<int, string>}
     */
    public function import(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if (! is_string($path) || $path === '') {
            throw new InvalidArgumentException('The uploaded workbook could not be read.');
        }

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
        } catch (\Throwable $exception) {
            Log::warning('Admin user import workbook could not be opened.', [
                'error' => $exception->getMessage(),
            ]);

            throw new InvalidArgumentException('The uploaded file is not a readable Excel workbook.');
        }

        try {
            $worksheet = $spreadsheet->getSheetByName('Users') ?? $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestDataRow();

            if ($highestRow < 1) {
                throw new InvalidArgumentException('The workbook is empty.');
            }

            $headers = [];

            foreach (array_keys(self::HEADERS) as $index) {
                $column = Coordinate::stringFromColumnIndex($index + 1);
                $headers[] = Str::of((string) $worksheet->getCell($column.'1')->getValue())
                    ->trim()
                    ->lower()
                    ->replace([' ', '-'], '_')
                    ->toString();
            }

            if ($headers !== self::HEADERS) {
                throw new InvalidArgumentException(
                    'The Users sheet headings do not match the template. Download a fresh template and keep its columns unchanged.'
                );
            }

            if (($highestRow - 1) > self::MAX_ROWS) {
                throw new InvalidArgumentException('A workbook can contain at most '.self::MAX_ROWS.' user rows.');
            }

            $rows = $this->readRows($worksheet, $highestRow);
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        if ($rows === []) {
            throw new InvalidArgumentException('No user rows were found in the Users sheet.');
        }

        return $this->persistRows($rows);
    }

    /**
     * @return array<int, array{row: int, name: string, email: string, role: string, company: string, phone: string, country: string, region_state: string, bio: string}>
     */
    private function readRows(Worksheet $worksheet, int $highestRow): array
    {
        $rows = [];

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $values = [];

            foreach (array_keys(self::HEADERS) as $index) {
                $column = Coordinate::stringFromColumnIndex($index + 1);
                $value = $worksheet->getCell($column.$rowNumber)->getValue();

                if ($value instanceof RichText) {
                    $value = $value->getPlainText();
                }

                $values[] = trim((string) ($value ?? ''));
            }

            if (collect($values)->every(fn (string $value): bool => $value === '')) {
                continue;
            }

            $row = array_combine(self::HEADERS, $values);
            $row['row'] = $rowNumber;
            $row['email'] = Str::lower($row['email']);
            $row['role'] = Str::lower($row['role']);
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{created: int, skipped: int, failed: int, errors: array<int, string>}
     */
    private function persistRows(array $rows): array
    {
        $result = [
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];
        $seenEmails = [];
        $emails = collect($rows)
            ->pluck('email')
            ->filter()
            ->unique()
            ->values();
        $existingEmails = User::query()
            ->whereIn(DB::raw('LOWER(email)'), $emails->all())
            ->pluck('email')
            ->mapWithKeys(fn (string $email): array => [Str::lower($email) => true])
            ->all();
        $countryLookup = Country::query()
            ->get(['name', 'code'])
            ->flatMap(fn (Country $country): array => [
                Str::lower($country->name) => $country->name,
                Str::lower($country->code) => $country->name,
            ])
            ->all();
        $unusablePassword = Hash::make(Str::random(64));

        foreach ($rows as $row) {
            $rowNumber = (int) $row['row'];
            $role = $row['role'] === 'creator' ? 'seller' : $row['role'];
            $validator = Validator::make([
                ...$row,
                'role' => $role,
            ], [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email:rfc', 'max:255'],
                'role' => ['required', 'in:user,seller'],
                'company' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'country' => ['nullable', 'string', 'max:100'],
                'region_state' => ['nullable', 'string', 'max:100'],
                'bio' => ['nullable', 'string', 'max:5000'],
            ]);

            if ($validator->fails()) {
                $this->recordError($result, $rowNumber, $validator->errors()->first());

                continue;
            }

            $email = $row['email'];

            if (isset($seenEmails[$email])) {
                $this->recordError($result, $rowNumber, 'Email is repeated in this workbook.');

                continue;
            }

            $seenEmails[$email] = true;

            if (isset($existingEmails[$email])) {
                $result['skipped']++;

                continue;
            }

            $country = null;

            if ($row['country'] !== '') {
                $country = $countryLookup[Str::lower($row['country'])] ?? null;

                if ($country === null) {
                    $this->recordError($result, $rowNumber, 'Country was not found. Use a country name or ISO code from the system list.');

                    continue;
                }
            }

            try {
                DB::transaction(function () use ($row, $role, $country, $unusablePassword): void {
                    $user = User::create([
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'password' => $unusablePassword,
                        'role' => $role,
                        'is_seller_approved' => $role === 'seller',
                    ]);

                    if ($role === 'seller') {
                        SellerProfile::create([
                            'user_id' => $user->id,
                            'company' => $row['company'] ?: null,
                            'phone' => $row['phone'] ?: null,
                            'country' => $country,
                            'region_state' => $row['region_state'] ?: null,
                            'bio' => $row['bio'] ?: null,
                        ]);
                    }
                });

                $result['created']++;
                $existingEmails[$email] = true;
            } catch (QueryException $exception) {
                Log::warning('Admin user import row could not be saved.', [
                    'row' => $rowNumber,
                    'email' => $email,
                    'error_code' => $exception->getCode(),
                ]);
                $this->recordError($result, $rowNumber, 'The account could not be created. Its email may already exist.');
            }
        }

        return $result;
    }

    /**
     * @param  array{created: int, skipped: int, failed: int, errors: array<int, string>}  $result
     */
    private function recordError(array &$result, int $rowNumber, string $message): void
    {
        $result['failed']++;

        if (count($result['errors']) < 100) {
            $result['errors'][] = "Row {$rowNumber}: {$message}";
        }
    }
}
