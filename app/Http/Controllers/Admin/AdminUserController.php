<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Imports\UserSpreadsheetImporter;
use App\Support\AdminSidebarBadges;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\File;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        AdminSidebarBadges::markAsSeen('users');
        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        $users = $query->withCount(['products', 'orders', 'revenues'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator(config('app.name'))
            ->setTitle('User and creator import template');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Users');
        $sheet->fromArray(UserSpreadsheetImporter::HEADERS, null, 'A1');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:H1');
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0A4B76'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A1:C1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('9F1239');
        $sheet->getRowDimension(1)->setRowHeight(24);

        foreach ([
            'A1' => 'REQUIRED: The account owner’s full name. Maximum 255 characters.',
            'B1' => 'REQUIRED: A valid, unique email address. Existing accounts are skipped.',
            'C1' => 'REQUIRED: Use only user or creator. Admin is not accepted.',
            'D1' => 'OPTIONAL: Creator company or brand name. Ignored for user accounts.',
            'E1' => 'OPTIONAL: Creator phone number. Keep the country code, for example +2348012345678.',
            'F1' => 'OPTIONAL: Creator country. Use a system country name or two-letter ISO code.',
            'G1' => 'OPTIONAL: Creator state, province or region.',
            'H1' => 'OPTIONAL: Short creator biography. Maximum 5,000 characters.',
        ] as $cell => $guidance) {
            $comment = $sheet->getComment($cell);
            $comment->getText()->createTextRun($guidance);
            $comment->setWidth('320pt')->setHeight('90pt');
        }

        foreach (['B', 'E'] as $textColumn) {
            $sheet->getStyle($textColumn.'2:'.$textColumn.'1001')
                ->getNumberFormat()
                ->setFormatCode('@');
        }

        foreach ([
            'A' => 28,
            'B' => 34,
            'C' => 16,
            'D' => 26,
            'E' => 20,
            'F' => 22,
            'G' => 22,
            'H' => 48,
        ] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        for ($row = 2; $row <= 1001; $row++) {
            $validation = $sheet->getCell('C'.$row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(false);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setErrorTitle('Invalid role');
            $validation->setError('Choose user or creator.');
            $validation->setFormula1('"user,creator"');
        }

        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('Instructions');
        $instructions->fromArray([
            ['ProjectRim user and creator import guide', '', '', ''],
            ['Required headers are dark red on the Users sheet. Optional headers are blue.', '', '', ''],
            ['', '', '', ''],
            ['Step', 'How to prepare a successful import', '', ''],
            ['1', 'Review the sample rows on the Examples sheet, then enter real accounts only on the Users sheet.', '', ''],
            ['2', 'Keep row 1 and all eight column names exactly as supplied. Do not rename, remove, add or reorder columns.', '', ''],
            ['3', 'Enter one account per row. Complete name, email and role for every row.', '', ''],
            ['4', 'In role, use only user or creator. Do not use seller, admin or any other value.', '', ''],
            ['5', 'Creator profile fields are optional. They are ignored when the role is user.', '', ''],
            ['6', 'Save the workbook as .xlsx or .xls, keep it below 5 MB, then upload it from Admin > Users.', '', ''],
            ['7', 'Review the import summary. Correct only rejected rows in a fresh template and upload them again.', '', ''],
            ['', '', '', ''],
            ['Column', 'Required?', 'Accepted values', 'Notes'],
            ['name', 'Required', 'Text, maximum 255 characters', 'Full name of the account owner.'],
            ['email', 'Required', 'Valid, unique email address', 'Existing emails are skipped and never overwritten.'],
            ['role', 'Required', 'user or creator only', 'Use the exact values shown. Admin accounts cannot be imported.'],
            ['company', 'Optional', 'Text, maximum 255 characters', 'Creator only: company, studio or brand name.'],
            ['phone', 'Optional', 'Text, maximum 50 characters', 'Creator only. Include the country code where available.'],
            ['country', 'Optional', 'System country name or two-letter ISO code', 'Creator only. Examples: Nigeria or NG.'],
            ['region_state', 'Optional', 'Text, maximum 100 characters', 'Creator only: state, province or region.'],
            ['bio', 'Optional', 'Text, maximum 5,000 characters', 'Creator only: short professional biography.'],
            ['', '', '', ''],
            ['Important rule', 'What happens', '', ''],
            ['Blank rows', 'They are ignored.', '', ''],
            ['Duplicate email in this file', 'The first valid row is used; later repeated rows are rejected.', '', ''],
            ['Existing account email', 'The row is skipped. Existing user data is never changed.', '', ''],
            ['Invalid row', 'Other valid rows still import; the failed row is listed in the result.', '', ''],
            ['Password and verification', 'No password is stored in Excel. The owner uses Forgot password, then verifies the email.', '', ''],
            ['Workbook limit', 'Maximum 1,000 account rows and 5 MB.', '', ''],
        ], null, 'A1');
        $instructions->mergeCells('A1:D1');
        $instructions->mergeCells('A2:D2');

        foreach (range(5, 11) as $row) {
            $instructions->mergeCells("B{$row}:D{$row}");
        }

        foreach (range(24, 29) as $row) {
            $instructions->mergeCells("B{$row}:D{$row}");
        }

        $instructions->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0A4B76']],
        ]);
        $instructions->getStyle('A4:D4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0A4B76']],
        ]);
        $instructions->getStyle('A13:D13')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0A4B76']],
        ]);
        $instructions->getStyle('A23:D23')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0A4B76']],
        ]);
        $instructions->getStyle('B14:B16')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '9F1239']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF1F2']],
        ]);
        $instructions->getStyle('B17:B21')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '0A4B76']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
        ]);
        $instructions->getColumnDimension('A')->setWidth(24);
        $instructions->getColumnDimension('B')->setWidth(30);
        $instructions->getColumnDimension('C')->setWidth(42);
        $instructions->getColumnDimension('D')->setWidth(54);
        $instructions->getStyle('A1:D29')->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_TOP);
        $instructions->getStyle('A13:D21')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setRGB('CBD5E1');
        $instructions->freezePane('A4');

        $examples = $spreadsheet->createSheet();
        $examples->setTitle('Examples');
        $examples->fromArray(UserSpreadsheetImporter::HEADERS, null, 'A1');
        $examples->fromArray([
            ['Amina Bello', 'amina.user@example.com', 'user', '', '', '', '', ''],
            ['Chinedu Okafor', 'chinedu.creator@example.com', 'creator', 'Okafor Design Studio', '', 'NG', 'Lagos', 'Digital product creator and educator.'],
        ], null, 'A2');
        $examples->setCellValueExplicit('E3', '+2348012345678', DataType::TYPE_STRING);
        $examples->freezePane('A2');
        $examples->setAutoFilter('A1:H3');
        $examples->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0A4B76']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $examples->getStyle('A1:C1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('9F1239');
        $examples->getStyle('A2:H3')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setRGB('CBD5E1');
        $examples->getStyle('B2:B3')->getNumberFormat()->setFormatCode('@');
        $examples->getStyle('E2:E3')->getNumberFormat()->setFormatCode('@');

        foreach ([
            'A' => 28,
            'B' => 34,
            'C' => 16,
            'D' => 26,
            'E' => 20,
            'F' => 22,
            'G' => 22,
            'H' => 48,
        ] as $column => $width) {
            $examples->getColumnDimension($column)->setWidth($width);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'projectrim-user-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function import(Request $request, UserSpreadsheetImporter $importer): RedirectResponse
    {
        $validated = $request->validate([
            'import_file' => [
                'required',
                File::types(['xlsx', 'xls'])->max(5 * 1024),
            ],
        ], [
            'import_file.required' => 'Choose an Excel workbook to import.',
            'import_file.mimes' => 'Upload an .xlsx or .xls workbook.',
        ]);

        try {
            $result = $importer->import($validated['import_file']);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['import_file' => $exception->getMessage()]);
        }

        $message = "Import complete: {$result['created']} created, {$result['skipped']} existing skipped";

        if ($result['failed'] > 0) {
            $message .= ", {$result['failed']} failed";
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', $message.'.')
            ->with('user_import_result', $result);
    }

    public function show(User $user)
    {
        $user->loadCount(['products', 'orders', 'downloads', 'revenues']);
        $user->load('sellerProfile');

        $totalRevenue = $user->revenues()->sum('amount_usd');
        $totalPaidOut = $user->payoutRequests()->where('status', 'paid')->sum('amount_usd');
        $balance = $totalRevenue - $totalPaidOut;

        return view('admin.users.show', compact('user', 'totalRevenue', 'totalPaidOut', 'balance'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|in:user,seller,admin',
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.show', $user)->with('success', 'User updated successfully.');
    }

    public function impersonate(Request $request, User $user)
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'Admin accounts cannot be impersonated.');
        }

        if ((string) $user->getKey() === (string) $request->user()?->getAuthIdentifier()) {
            return back()->with('error', 'You cannot impersonate your own account.');
        }

        $impersonatorId = $request->user()?->getAuthIdentifier();

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('impersonator_id', $impersonatorId);

        return redirect()->route('dashboard')->with('status', "You are now logged in as {$user->name}.");
    }

    public function stopImpersonating(Request $request)
    {
        $impersonatorId = $request->session()->pull('impersonator_id');

        if (! $impersonatorId) {
            return redirect()->route('dashboard');
        }

        $admin = User::query()
            ->whereKey($impersonatorId)
            ->where('role', 'admin')
            ->firstOrFail();

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.users.index')->with('success', 'Returned to your admin account.');
    }

    public function destroy(Request $request, User $user)
    {
        if ((string) $user->getKey() === (string) $request->user()?->getAuthIdentifier()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        User::destroy($user->getKey());

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
