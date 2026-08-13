<?php

use App\Models\Country;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

test('admin can download the user import template', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('admin.users.import-template'));

    $response->assertOk();
    $response->assertHeader(
        'content-type',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    );
    expect($response->headers->get('content-disposition'))
        ->toContain('projectrim-user-import-template.xlsx');

    $path = tempnam(sys_get_temp_dir(), 'projectrim-template-');

    try {
        file_put_contents($path, $response->streamedContent());
        $workbook = IOFactory::load($path);

        expect($workbook->getSheetNames())->toBe(['Users', 'Instructions', 'Examples']);
        $usersSheet = $workbook->getSheetByName('Users');
        $instructionsSheet = $workbook->getSheetByName('Instructions');
        $examplesSheet = $workbook->getSheetByName('Examples');

        expect($usersSheet?->rangeToArray('A1:H1')[0])->toBe([
            'name',
            'email',
            'role',
            'company',
            'phone',
            'country',
            'region_state',
            'bio',
        ])
            ->and($usersSheet?->getComment('A1')->getText()->getPlainText())->toContain('REQUIRED')
            ->and($usersSheet?->getComment('D1')->getText()->getPlainText())->toContain('OPTIONAL')
            ->and($instructionsSheet?->getCell('B16')->getValue())->toBe('Required')
            ->and($instructionsSheet?->getCell('C16')->getValue())->toBe('user or creator only')
            ->and($instructionsSheet?->getCell('B17')->getValue())->toBe('Optional')
            ->and($examplesSheet?->getCell('C2')->getValue())->toBe('user')
            ->and($examplesSheet?->getCell('C3')->getValue())->toBe('creator')
            ->and($examplesSheet?->getCell('E3')->getValue())->toBe('+2348012345678');

        $workbook->disconnectWorksheets();
    } finally {
        if (is_string($path) && is_file($path)) {
            unlink($path);
        }
    }
});

test('admin can import users and approved creators from a workbook', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Country::create(['name' => 'Nigeria', 'code' => 'NG']);
    $upload = createProjectrimUserImportUpload([
        ['Ada User', 'ada@example.com', 'user', '', '', '', '', ''],
        ['Tunde Creator', 'tunde@example.com', 'creator', 'Tunde Studio', '+2348012345678', 'NG', 'Lagos', 'Digital creator'],
    ]);

    $response = $this->actingAs($admin)->post(route('admin.users.import'), [
        'import_file' => $upload,
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('user_import_result', function (array $result): bool {
        return $result['created'] === 2
            && $result['skipped'] === 0
            && $result['failed'] === 0;
    });

    $this->assertDatabaseHas('users', [
        'email' => 'ada@example.com',
        'role' => 'user',
        'email_verified_at' => null,
    ]);
    $this->assertDatabaseHas('users', [
        'email' => 'tunde@example.com',
        'role' => 'seller',
        'is_seller_approved' => true,
        'email_verified_at' => null,
    ]);

    $creator = User::query()->where('email', 'tunde@example.com')->firstOrFail();

    expect($creator->password)->not->toBeNull()
        ->and(Hash::needsRehash($creator->password))->toBeFalse();

    $this->assertDatabaseHas('seller_profiles', [
        'user_id' => $creator->id,
        'company' => 'Tunde Studio',
        'phone' => '+2348012345678',
        'country' => 'Nigeria',
        'region_state' => 'Lagos',
        'bio' => 'Digital creator',
    ]);
});

test('import skips existing emails while reporting invalid and repeated rows', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->create(['email' => 'existing@example.com']);
    Country::create(['name' => 'Nigeria', 'code' => 'NG']);
    $upload = createProjectrimUserImportUpload([
        ['Existing User', 'existing@example.com', 'user', '', '', '', '', ''],
        ['Valid User', 'new@example.com', 'user', '', '', '', '', ''],
        ['Repeated User', 'NEW@example.com', 'creator', '', '', 'NG', '', ''],
        ['Wrong Role', 'admin@example.com', 'admin', '', '', '', '', ''],
        ['Unknown Country', 'country@example.com', 'creator', '', '', 'Atlantis', '', ''],
    ]);

    $response = $this->actingAs($admin)->post(route('admin.users.import'), [
        'import_file' => $upload,
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('user_import_result', function (array $result): bool {
        return $result['created'] === 1
            && $result['skipped'] === 1
            && $result['failed'] === 3
            && count($result['errors']) === 3;
    });
    $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'role' => 'user']);
    $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
    $this->assertDatabaseMissing('users', ['email' => 'country@example.com']);
});

test('import rejects workbooks whose columns do not match the template', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $upload = createProjectrimUserImportUpload(
        [['Person', 'person@example.com', 'user']],
        ['full_name', 'email', 'role']
    );

    $response = $this->actingAs($admin)->from(route('admin.users.index'))->post(
        route('admin.users.import'),
        ['import_file' => $upload]
    );

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHasErrors('import_file');
    $this->assertDatabaseMissing('users', ['email' => 'person@example.com']);
});

test('non admins cannot import users', function () {
    $user = User::factory()->create(['role' => 'user']);
    $upload = createProjectrimUserImportUpload([
        ['Blocked User', 'blocked@example.com', 'user', '', '', '', '', ''],
    ]);

    $this->actingAs($user)
        ->post(route('admin.users.import'), ['import_file' => $upload])
        ->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);
});

/**
 * @param  array<int, array<int, string>>  $rows
 * @param  array<int, string>|null  $headers
 */
function createProjectrimUserImportUpload(array $rows, ?array $headers = null): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Users');
    $sheet->fromArray($headers ?? [
        'name',
        'email',
        'role',
        'company',
        'phone',
        'country',
        'region_state',
        'bio',
    ], null, 'A1');
    $sheet->fromArray($rows, null, 'A2');

    foreach ($rows as $index => $row) {
        if (($row[4] ?? '') !== '') {
            $sheet->setCellValueExplicit('E'.($index + 2), (string) $row[4], DataType::TYPE_STRING);
        }
    }

    $path = tempnam(sys_get_temp_dir(), 'projectrim-user-import-');
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    register_shutdown_function(static function () use ($path): void {
        if (is_file($path)) {
            unlink($path);
        }
    });

    return new UploadedFile(
        $path,
        'projectrim-user-import.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );
}
