<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Challan {{ $data['challan']['number'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .wrapper { width: 100%; }
        .header { border: 1px solid #d1d5db; padding: 12px; margin-bottom: 12px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .logo { width: 64px; height: 64px; object-fit: contain; }
        .title { font-size: 18px; font-weight: 700; margin: 0; }
        .subtitle { font-size: 11px; color: #4b5563; margin: 4px 0 0; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta td { border: 1px solid #d1d5db; padding: 6px; }
        .meta-label { font-size: 10px; color: #6b7280; }
        .meta-value { font-size: 12px; font-weight: 600; margin-top: 2px; }
        .items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .items th, .items td { border: 1px solid #d1d5db; padding: 6px; }
        .items th { background: #f3f4f6; text-align: left; font-size: 11px; }
        .text-right { text-align: right; }
        .summary { margin-top: 10px; width: 100%; border-collapse: collapse; }
        .summary td { border: 1px solid #d1d5db; padding: 6px; }
        .summary td.label { background: #f9fafb; width: 70%; font-weight: 600; }
        .signatures { margin-top: 28px; width: 100%; border-collapse: collapse; }
        .signatures td { width: 50%; padding-top: 20px; text-align: center; }
        .line { border-top: 1px solid #111827; width: 75%; margin: 0 auto 4px; }
    </style>
</head>
<body>
    <div class="wrapper">
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
                        <p class="subtitle">Fee Challan</p>
                    </td>
                    <td style="text-align:right;">
                        <p class="subtitle">Challan #</p>
                        <p class="title" style="font-size:14px;">{{ $data['challan']['number'] }}</p>
                    </td>
                </tr>
            </table>
        </div>

        <table class="meta">
            <tr>
                <td>
                    <div class="meta-label">Student Name</div>
                    <div class="meta-value">{{ $data['student']['name'] }}</div>
                </td>
                <td>
                    <div class="meta-label">Student ID</div>
                    <div class="meta-value">{{ $data['student']['student_id'] }}</div>
                </td>
                <td>
                    <div class="meta-label">Class</div>
                    <div class="meta-value">{{ $data['student']['class'] }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="meta-label">Session</div>
                    <div class="meta-value">{{ $data['challan']['session'] }}</div>
                </td>
                <td>
                    <div class="meta-label">Month</div>
                    <div class="meta-value">{{ $data['challan']['month_label'] }}</div>
                </td>
                <td>
                    <div class="meta-label">Due Date</div>
                    <div class="meta-value">{{ $data['challan']['due_date'] }}</div>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 55%;">Fee Head</th>
                    <th style="width: 25%;">Fee Type</th>
                    <th class="text-right" style="width: 20%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['items'] as $item)
                    <tr>
                        <td>{{ $item['title'] }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $item['fee_type'])) }}</td>
                        <td class="text-right">{{ number_format((float) $item['amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align:center;">No fee heads found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="summary">
            <tr>
                <td class="label">Total Amount</td>
                <td class="text-right">{{ number_format((float) $data['summary']['total_amount'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Paid Amount</td>
                <td class="text-right">{{ number_format((float) $data['summary']['paid_amount'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Remaining Amount</td>
                <td class="text-right">{{ number_format((float) $data['summary']['remaining_amount'], 2) }}</td>
            </tr>
        </table>

        <table class="signatures">
            <tr>
                <td>
                    <div class="line"></div>
                    Student/Parent Signature
                </td>
                <td>
                    <div class="line"></div>
                    Accounts/Principal Signature
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
