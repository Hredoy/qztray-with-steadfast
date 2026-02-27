<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Print' }}</title>
    <style>
        /* Simple print-friendly styles */
        body { font-family: Arial, sans-serif; margin: 0; padding: 16px; color: #111; }
        .box { border: 1px solid #ddd; border-radius: 8px; padding: 12px; }
        h1,h2,h3 { margin: 0 0 10px 0; }
        .muted { color: #666; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 8px; border-bottom: 1px solid #eee; text-align: left; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
@yield('content')
</body>
</html>
