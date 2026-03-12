<?php

declare(strict_types=1);

use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

$outputDir = storage_path('app/exports');
if (! is_dir($outputDir) && ! mkdir($outputDir, 0777, true) && ! is_dir($outputDir)) {
    fwrite(STDERR, "Unable to create export directory: {$outputDir}\n");
    exit(1);
}

$users = User::query()
    ->with('roles:id,name')
    ->orderBy('id')
    ->get(['id', 'name', 'email', 'status', 'password', 'must_change_password', 'password_changed_at', 'created_at']);

$rows = [];
foreach ($users as $user) {
    $roles = $user->roles->pluck('name')->implode(', ');
    $passwordHint = 'Unknown / Changed';

    if (Hash::check('Teacher@123', (string) $user->password)) {
        $passwordHint = 'Teacher@123';
    } elseif (Hash::check('password', (string) $user->password)) {
        $passwordHint = 'password';
    }

    $rows[] = [
        'id' => (int) $user->id,
        'name' => (string) $user->name,
        'email' => (string) $user->email,
        'roles' => $roles !== '' ? $roles : '-',
        'status' => (string) ($user->status ?? 'active'),
        'must_change' => (bool) $user->must_change_password ? 'Yes' : 'No',
        'password_hint' => $passwordHint,
        'created_at' => $user->created_at ? $user->created_at->format('Y-m-d H:i') : '-',
    ];
}

$generatedAt = now()->format('Y-m-d H:i:s');
$title = 'User Login List';

$html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; }
        h1 { margin: 0 0 6px 0; font-size: 18px; }
        .meta { margin-bottom: 10px; font-size: 10px; color: #334155; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; vertical-align: top; }
        th { background: #f1f5f9; text-align: left; font-weight: bold; }
        tr:nth-child(even) td { background: #f8fafc; }
        .note { margin-top: 10px; font-size: 9px; color: #475569; }
    </style>
</head>
<body>
    <h1>'.$title.'</h1>
    <div class="meta">Generated: '.$generatedAt.' | Total Users: '.count($rows).'</div>
    <table>
        <thead>
            <tr>
                <th style="width:4%;">ID</th>
                <th style="width:18%;">Name</th>
                <th style="width:22%;">Login Email</th>
                <th style="width:14%;">Role(s)</th>
                <th style="width:8%;">Status</th>
                <th style="width:9%;">Must Change Password</th>
                <th style="width:11%;">Password Hint</th>
                <th style="width:14%;">Created At</th>
            </tr>
        </thead>
        <tbody>';

foreach ($rows as $row) {
    $html .= '<tr>
        <td>'.e((string) $row['id']).'</td>
        <td>'.e($row['name']).'</td>
        <td>'.e($row['email']).'</td>
        <td>'.e($row['roles']).'</td>
        <td>'.e($row['status']).'</td>
        <td>'.e($row['must_change']).'</td>
        <td>'.e($row['password_hint']).'</td>
        <td>'.e($row['created_at']).'</td>
    </tr>';
}

$html .= '</tbody>
    </table>
    <div class="note">
        Password Hint shows only known default values detected from hash checks (e.g. password or Teacher@123). 
        "Unknown / Changed" means the account password does not match known defaults.
    </div>
</body>
</html>';

$options = new Options();
$options->set('isRemoteEnabled', false);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$timestamp = now()->format('Ymd_His');
$pdfPath = $outputDir.DIRECTORY_SEPARATOR."user_login_list_{$timestamp}.pdf";
file_put_contents($pdfPath, $dompdf->output());

echo $pdfPath.PHP_EOL;

