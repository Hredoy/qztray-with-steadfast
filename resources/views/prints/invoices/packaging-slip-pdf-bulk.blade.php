<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        html, body { margin: 0; padding: 0; }

        /* Each slip is exactly one page */
        .slip-page{
            width: 216pt;
            height: 288pt;
            box-sizing: border-box;
            overflow: hidden;
            page-break-after: always;   /* important */
        }
        .slip-page:last-child{
            page-break-after: auto;
        }
    </style>
</head>
<body>

@foreach($slips as $s)
    <div class="slip-page">
        @include('prints.invoices.packaging-slip-pdf', [
            'invoice' => $s['invoice'],
            'qrBase64' => $s['qrBase64'],
            'barcodeBase64' => $s['barcodeBase64'],
            'barcodeText' => $s['barcodeText'],
            'brandLogo' => $brandLogo,
            'miniLogo' => $miniLogo,
            'packLogo' => $packLogo,
            'courierLogo' => $courierLogo,
        ])
    </div>
@endforeach

</body>
</html>
