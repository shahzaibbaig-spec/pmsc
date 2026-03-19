<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Student ID Cards</title>
    <style>
        @page { margin: 8mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #0f172a; margin: 0; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }

        .page-header { margin-bottom: 4mm; }
        .heading { margin: 0; font-size: 13px; font-weight: 700; }
        .meta { margin: 1mm 0 0 0; font-size: 9px; color: #334155; }

        .sheet { width: 100%; border-collapse: separate; border-spacing: 4mm 3.8mm; }
        .sheet td { width: 50%; vertical-align: top; }

        .id-card { width: 86mm; height: 54mm; border: 0.6mm solid #0f172a; border-radius: 2mm; overflow: hidden; }
        .id-card-table { width: 100%; height: 100%; border-collapse: collapse; table-layout: fixed; }
        .header-cell { background: #e2e8f0; border-bottom: 0.4mm solid #94a3b8; padding: 0.8mm 1.2mm; }
        .header-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .logo-cell { width: 12mm; vertical-align: middle; text-align: left; }
        .title-cell { vertical-align: middle; text-align: left; }
        .logo { width: 10mm; height: 10mm; object-fit: contain; }
        .school-name { margin: 0; font-size: 9px; font-weight: 700; line-height: 1.15; }
        .sub-title { margin: 0.5mm 0 0 0; font-size: 7px; color: #334155; }

        .photo-cell { width: 20mm; padding: 1.2mm; vertical-align: top; }
        .photo { width: 17mm; height: 21mm; border: 0.3mm solid #94a3b8; object-fit: cover; }
        .photo-placeholder { width: 17mm; height: 21mm; border: 0.3mm dashed #94a3b8; font-size: 6px; color: #475569; line-height: 21mm; text-align: center; }

        .meta-cell { padding: 1.2mm 0.8mm; vertical-align: top; }
        .meta-row { margin: 0 0 1.1mm 0; font-size: 6.3px; line-height: 1.2; }
        .label { font-weight: 700; color: #334155; }

        .qr-cell { width: 16mm; text-align: center; vertical-align: middle; padding: 0.8mm; }
        .qr { width: 13.5mm; height: 13.5mm; object-fit: contain; }
    </style>
</head>
<body>
    @php($pages = array_chunk($cards, 8))
    @if (count($pages) === 0)
        <div class="page">
            <div class="page-header">
                <p class="heading">{{ $school['name'] }} - Student ID Cards</p>
                <p class="meta">Class: {{ trim($class->name.' '.($class->section ?? '')) }}</p>
            </div>
            <p class="meta">No active students found for this class.</p>
        </div>
    @else
        @foreach($pages as $pageIndex => $pageCards)
            <div class="page">
                <div class="page-header">
                    <p class="heading">{{ $school['name'] }} - Student ID Cards</p>
                    <p class="meta">Class: {{ trim($class->name.' '.($class->section ?? '')) }} | Page {{ $pageIndex + 1 }} of {{ count($pages) }}</p>
                </div>

                <table class="sheet">
                    @foreach(array_chunk($pageCards, 2) as $rowCards)
                        <tr>
                            @foreach($rowCards as $card)
                                <td>
                                    @include('idcards.partials.card', ['card' => $card, 'school' => $school])
                                </td>
                            @endforeach
                            @if(count($rowCards) < 2)
                                <td></td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach
    @endif
</body>
</html>
