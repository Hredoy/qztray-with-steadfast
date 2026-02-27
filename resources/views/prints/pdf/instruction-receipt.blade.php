<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body {
            margin: 0;
            padding: 10px;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #000;
        }

        .title { font-size: 12px; font-weight: bold; margin-bottom: 6px; }
        .meta { font-size: 9px; color: #333; margin-bottom: 8px; }
        .hr { border-top: 1px solid #ddd; margin: 6px 0; }

        .body {
            font-size: 9px;
            line-height: 1.3;

            /* Ensure no overflow */
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: break-all;
        }
    </style>
</head>
<body>

<div class="title">Instruction</div>
<div class="meta">
    Invoice: {{ $invoice->invoice }} | Phone: {{ $invoice->phone }}
</div>
<div class="hr"></div>

<div class="body">
    {{ $invoice->instruction ?: 'No instruction found.' }}
</div>

</body>
</html>
