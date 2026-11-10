<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>{{ $title }} · Advokat Propindo</title>
    <style>
        @page { margin: 118px 28px 55px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8px; color: #182e24; line-height: 1.4; }
        header { position: fixed; top: -98px; left: 0; right: 0; height: 83px; border-bottom: 3px double #176b56; text-align: center; }
        header h1 { margin: 0; font-size: 22px; letter-spacing: 3px; color: #176b56; }
        header p { margin: 3px 0; font-size: 10px; }
        footer { position: fixed; bottom: -35px; left: 0; right: 0; border-top: 1px solid #dce3dc; padding-top: 5px; color: #62746a; font-size: 7px; }
        .page-number:after { content: counter(page); }
        h2 { font-size: 13px; text-align: center; margin: 0 0 9px; }
        h3 { font-size: 10px; margin: 12px 0 6px; }
        p { margin: 5px 0; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { border: 1px solid #dce3dc; padding: 5px 4px; vertical-align: top; overflow-wrap: break-word; word-wrap: break-word; }
        th { background: #eaf4ef; font-size: 7px; }
        .meta { color: #4c6055; font-size: 8px; margin-bottom: 9px; }
        .low { color: #b91c1c; font-weight: bold; }
        .number { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .identifier { white-space: nowrap; }
        .dot { display: inline-block; width: 6px; height: 6px; margin-right: 4px; border-radius: 50%; background: #b91c1c; }
        .note { background: #f6f8f5; font-size: 7px; color: #4c6055; }
        .muted { color: #62746a; }
        .signature { margin-top: 20px; text-align: right; font-size: 9px; }
        .no-border td { border: 0; }
        .keep { page-break-inside: avoid; }
    </style>
</head>
<body>
    <header>
        <h1>ADVOKAT PROPINDO</h1>
        <p>Pendidikan Khusus Profesi Advokat (PKPA)</p>
        <p>Sistem Akademik PKPA</p>
    </header>
    <footer>
        Dicetak {{ $printedAt->format('d/m/Y H:i') }} · Oleh {{ $printedBy }}
        <span style="float:right">Halaman <span class="page-number"></span></span>
    </footer>
    <h2>{{ $title }}</h2>
    @yield('content')
</body>
</html>
