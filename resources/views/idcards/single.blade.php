<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID Card</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #0f172a; margin: 0; }
        .canvas { width: 100%; min-height: 260mm; text-align: center; }
        .card-wrap { display: inline-block; margin-top: 24mm; }

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
    <div class="canvas">
        <div class="card-wrap">
            @include('idcards.partials.card', ['card' => $card, 'school' => $school])
        </div>
    </div>
</body>
</html>

