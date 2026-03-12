<!doctype html>
<html>
<head>
    <meta charset="utf-8">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Asap:ital,wght@0,100..900;1,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');
        @page { size: 3in 4in; margin: 10px; }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            height: 4in;
            width: 3in;
            font-family: "Poppins", sans-serif;
        }

        .sheet {
            margin: 0 !important;
            padding: 6pt;
            page-break-after: avoid !important;
            page-break-before: avoid !important;
        }

        .b-b{ border-bottom:2pt solid #000; }
        .b-t{ border-top:2pt solid #000; }
        .right{ text-align:right; }
        .center{ text-align:center; }
        .bold{ font-weight:800; }

        /* ===== FIXED ASSET SIZES (from your inches) ===== */
        .img-brand{ height:30pt; width:auto; }     /* 0.5672in */
        .img-qr{ height:30pt; width:30pt; }        /* 0.5385/0.5461in */
        .img-bar{ height:20pt; width:100%; }       /* 0.3959in */
        .img-mini{ height:35pt; width:auto; }      /* 0.5759in */
        .img-pack{ height:20pt; width:auto; }      /* 0.3643in */
        .img-steadfast{ height:20pt; width:auto; } /* 0.1806in */

        /* ===== HEADER ===== */
        .hdr{ padding-bottom:4pt; margin-bottom:4pt; }
        .title{ font-size:12pt; font-weight:800; margin:0; line-height:1.0; }
        .id{ font-size:11pt; font-weight:800; margin:0; line-height:1.0; }

        /* ===== BARCODE ===== */
        .bar{ padding:4pt 0 4pt; margin-bottom:4pt; }
        .barcode-text{ font-size:7.5pt; text-align:center; margin-top:1pt; }

        /* ===== META ===== */
        .meta{ font-size:8.5pt; padding:4pt 0; margin-bottom:4pt; }

        .addr-cod-wrap{
            height:120pt;        /* 1.9172in total */
            overflow:hidden;
        }

        .addr{
            border:1pt solid #000;
            border-radius:8pt;
            padding:5pt;
            height:70pt;         /* part of 138pt */
            box-sizing:border-box;
            position:relative;
            font-size:8.5pt;
            line-height:1.15;
            overflow:hidden;
            margin-bottom:4pt;
        }
        .wrap-anywhere{ overflow-wrap:anywhere; word-break:break-word; }

        .mini{
            position:absolute;
            right:5pt;
            top:15pt;
        }

        .cod{
            border:1pt solid #000;
            border-radius:8pt;
            overflow:hidden;
            width:100%;
        }
        .cod td{
            font-size:12pt;
            font-weight:800;
            padding:5pt;
        }
        .cod .l{ border-right:2pt solid #000; width:50%; }
        .cod .r{ width:50%; text-align:right; }

        .ftr{ padding-top:0pt; font-size:5pt; }
        .small{ font-size:5pt; font-weight:800; }

        *{ page-break-inside: avoid; }
    </style>

</head>

<body>
<div class="sheet">
    <div class="hdr b-b">
        <table width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td width="50pt" style="vertical-align:middle;">
                    <img src="{{ $brandLogo }}" class="img-brand" alt="Brand">
                </td>

                <td class="center" style="vertical-align:middle;">
                    <p class="title">Happy Pixel</p>
                    <p class="id">ID:1669042</p>
                </td>

                <td width="45pt" class="right" style="vertical-align:middle;">
                    <img src="{{ $qrBase64 }}" class="img-qr" alt="QR">
                </td>
            </tr>
        </table>
    </div>
    <div class="bar b-b">
        <img src="{{ $barcodeBase64 }}" class="img-bar" alt="Barcode">
    </div>
    <div class="meta">
        <table width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td>
                    Invoice: <span class="bold">{{ $invoice->invoice_id }}</span><br>
                    D.Type: <span class="bold">Home</span>
                </td>
                <td class="right">
                    SF-ID: <span class="bold">{{ $invoice->stead_fast_id }}</span><br>
                    WGT: <span class="bold">{{ $invoice->wgt }}</span>
                </td>
            </tr>
        </table>
    </div>
    <div class="addr-cod-wrap">
        <div class="addr" style="position: relative">
            <div style="width: 130pt">
                <div class="wrap-anywhere"><span class="bold">Name:</span> {{ $invoice->name }}</div>
                <div class="wrap-anywhere"><span class="bold">Phone:</span> {{ $invoice->phone }}</div>
                <div class="wrap-anywhere"><span class="bold">Address:</span> {{ $invoice->address }}</div>

            </div>

            <div class="mini">
                <img src="{{ $miniLogo }}" class="img-mini"  alt="Mini Logo">
            </div>
        </div>

        <table class="cod" cellspacing="0" cellpadding="0">
            <tr>
                <td class="l">COD</td>
                <td class="r">{{ $invoice->cod }}</td>
            </tr>
        </table>

    </div>
    <div class="ftr b-t">
        <table width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td width="70pt" style="vertical-align:bottom;">
                    <img src="{{ $packLogo }}" class="img-pack" alt="Packaging">
                </td>

                <td class="center small" style="vertical-align:bottom;">
                    thanks for purchase
                </td>

                <td width="80pt" class="right" style="vertical-align:bottom;">
                    <img src="{{ $courierLogo }}" class="img-steadfast" alt="Steadfast">
                    <div class="small">www.steadfast.com.bd</div>
                </td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
