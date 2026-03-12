<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salary Slip - {{ $data['employee']['name'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .header { border: 1px solid #d1d5db; padding: 12px; margin-bottom: 10px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .logo { width: 64px; height: 64px; object-fit: contain; }
        .title { font-size: 18px; font-weight: 700; margin: 0; }
        .subtitle { font-size: 11px; color: #4b5563; margin-top: 4px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .meta td { border: 1px solid #d1d5db; padding: 6px; }
        .meta-label { font-size: 10px; color: #6b7280; }
        .meta-value { font-size: 12px; font-weight: 600; margin-top: 2px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 6px; }
        .table th { background: #f3f4f6; text-align: left; font-size: 11px; }
        .text-right { text-align: right; }
        .summary { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .summary td { border: 1px solid #d1d5db; padding: 6px; }
        .summary .label { background: #f9fafb; width: 75%; font-weight: 600; }
        .signatures { width: 100%; border-collapse: collapse; margin-top: 28px; }
        .signatures td { width: 50%; text-align: center; padding-top: 22px; }
        .line { border-top: 1px solid #111827; width: 75%; margin: 0 auto 4px; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width:80px;">
                    @if(!empty($data['school']['logo_absolute_path']))
                        <img src="{{ $data['school']['logo_absolute_path'] }}" alt="School Logo" class="logo">
                    @endif
                </td>
                <td>
                    <p class="title">{{ $data['school']['name'] }}</p>
                    <p class="subtitle">Salary Slip</p>
                </td>
                <td style="text-align:right;">
                    <p class="subtitle">Payroll Month</p>
                    <p class="title" style="font-size:14px;">{{ $data['payroll']['month_label'] }}</p>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta">
        <tr>
            <td>
                <div class="meta-label">Employee Name</div>
                <div class="meta-value">{{ $data['employee']['name'] }}</div>
            </td>
            <td>
                <div class="meta-label">Employee Ref</div>
                <div class="meta-value">{{ $data['employee']['employee_ref'] }}</div>
            </td>
            <td>
                <div class="meta-label">Run Date</div>
                <div class="meta-value">{{ $data['payroll']['run_date'] }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="meta-label">Email</div>
                <div class="meta-value">{{ $data['employee']['email'] }}</div>
            </td>
            <td>
                <div class="meta-label">Bank Name</div>
                <div class="meta-value">{{ $data['bank']['bank_name'] ?: '-' }}</div>
            </td>
            <td>
                <div class="meta-label">Account No</div>
                <div class="meta-value">{{ $data['bank']['account_no'] ?: '-' }}</div>
            </td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th style="width:70%;">Allowance Components</th>
                <th class="text-right" style="width:30%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Basic Salary</td>
                <td class="text-right">{{ number_format((float) $data['components']['basic_salary'], 2) }}</td>
            </tr>
            @foreach($data['components']['allowances'] as $row)
                <tr>
                    <td>{{ $row['title'] }}</td>
                    <td class="text-right">{{ number_format((float) $row['amount'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th style="width:70%;">Deduction Components</th>
                <th class="text-right" style="width:30%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['components']['deductions'] as $row)
                <tr>
                    <td>{{ $row['title'] }}</td>
                    <td class="text-right">{{ number_format((float) $row['amount'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" style="text-align:center;">No deductions</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td class="label">Allowances Total</td>
            <td class="text-right">{{ number_format((float) $data['summary']['allowances_total'], 2) }}</td>
        </tr>
        <tr>
            <td class="label">Deductions Total</td>
            <td class="text-right">{{ number_format((float) $data['summary']['deductions_total'], 2) }}</td>
        </tr>
        <tr>
            <td class="label">Net Salary</td>
            <td class="text-right">{{ number_format((float) $data['summary']['net_salary'], 2) }}</td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div class="line"></div>
                Employee Signature
            </td>
            <td>
                <div class="line"></div>
                Accounts/Principal Signature
            </td>
        </tr>
    </table>
</body>
</html>
