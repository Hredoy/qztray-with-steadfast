<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Packaging Slip</title>
    <style>
        /* ====== PAGE SETTINGS ====== */
        @media print {
            @page { margin: 0; }
        }
        html, body { margin: 0; padding: 0; background: #fff; }
        body { font-family: Arial, sans-serif; color: #000; }

        /* Change this size if you need 4x6, A6, etc */
        .sheet {
            width: 95mm;           /* adjust */
            margin: 0 auto;
            padding: 6mm;
            box-sizing: border-box;
        }

        /* ====== HEADER ====== */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            border-bottom: 2px solid #000;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }

        .brand-left {
            width: 22mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .brand-left img {
            width: 100%;
            height: auto;
            display: block;
        }

        .brand-center {
            flex: 1;
            text-align: center;
            line-height: 1.1;
        }
        .brand-center .title {
            font-size: 22px;
            font-weight: 800;
            margin: 0;
        }
        .brand-center .id {
            font-size: 22px;
            font-weight: 800;
            margin: 0;
        }

        .qr-right {
            width: 22mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-right img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* ====== BARCODE ====== */
        .barcode-wrap {
            border-bottom: 2px solid #000;
            padding: 6px 0 8px;
        }
        .barcode-wrap img {
            width: 100%;
            height: auto;
            display: block;
        }
        .barcode-text {
            text-align: center;
            font-size: 11px;
            margin-top: 2px;
            letter-spacing: 0.5px;
        }

        /* ====== META ROW ====== */
        .meta {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 0;
            border-bottom: 2px solid #000;
            font-size: 16px;
        }
        .meta .left, .meta .right { width: 50%; }
        .meta .row { display: flex; justify-content: space-between; }
        .meta .label { font-weight: 400; }
        .meta .value { font-weight: 700; }

        /* ====== ADDRESS BOX ====== */
        .address-box {
            margin-top: 10px;
            border: 2px solid #000;
            border-radius: 12px;
            padding: 10px;
            min-height: 48mm;
            box-sizing: border-box;
            position: relative;
        }
        .address-line {
            font-size: 18px;
            margin: 2px 0;
        }
        .address-line b { font-weight: 800; }

        /* Small logo bottom-right (like your screenshot) */
        .mini-logo {
            position: absolute;
            right: 10px;
            bottom: 10px;
            width: 20mm;
            opacity: 1;
            text-align: center;
        }
        .mini-logo img {
            width: 100%;
            height: auto;
            display: block;
        }
        .mini-logo .mini-text {
            font-size: 7px;
            margin-top: 2px;
        }

        /* ====== COD BOX ====== */
        .cod-box {
            margin-top: 10px;
            border: 2px solid #000;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            font-size: 22px;
            font-weight: 800;
        }
        .cod-box .label {
            width: 50%;
            padding: 10px 12px;
            border-right: 2px solid #000;
        }
        .cod-box .amount {
            width: 50%;
            padding: 10px 12px;
            text-align: right;
        }

        /* ====== FOOTER ====== */
        .footer {
            margin-top: 10px;
            border-top: 2px solid #000;
            padding-top: 8px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 8px;
        }
        .icons {
            gap: 8px;
            align-items: center;
        }
        .icon {
            width: 14mm;
            height: 14mm;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #111;
            color: #fff;
            font-size: 10px;
            font-weight: 800;
        }

        .footer-mid {
            flex: 1;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
        }

        .courier {
            width: 30mm;
            text-align: right;
        }
        .courier img {
            width: 100%;
            height: auto;
            display: block;
        }
        .courier-site {
            font-size: 11px;
            font-weight: 700;
        }

        /* Utility: prevent overflow/wrap issues */
        .wrap-anywhere { overflow-wrap: anywhere; word-break: break-word; white-space: normal; }
    </style>
</head>
<body>
<div class="sheet">

    {{-- HEADER --}}
    <div class="header">
        <div class="brand-left">
            {{-- Replace with your brand logo --}}
            <img src="{{ asset('logo/SVG/Asset 5.svg') }}" alt="Brand">
        </div>

        <div class="brand-center">
            <p class="title">Happy Pixel</p>
            <p class="id">ID:{{ $invoice->id }}</p>
        </div>

        <div class="qr-right">
            <img src="{{ $qrBase64 }}" alt="QR">
        </div>
    </div>

    {{-- BARCODE --}}
    <div class="barcode-wrap">
        <img src="{{ $barcodeBase64 }}" alt="Barcode">
        <div class="barcode-text">{{ $barcodeText }}</div>
    </div>

    {{-- META (invoice / SF-ID / D.Type / WGT) --}}
    <div class="meta">
        <div class="left">
            <div class="row">
                <span class="label">Invoice:</span>
                <span class="value">{{ $invoice->invoice }}</span>
            </div>
            <div class="row">
                <span class="label">D. Type :</span>
                <span class="value">Home</span> {{-- replace with your value --}}
            </div>
        </div>

        <div class="right">
            <div class="row">
                <span class="label">SF-ID:</span>
                <span class="value">{{ $invoice->stead_fast_id }}</span>
            </div>
            <div class="row">
                <span class="label">WGT :</span>
                <span class="value">{{ $invoice->wgt }}</span>
            </div>
        </div>
    </div>

    {{-- ADDRESS BOX --}}
    <div class="address-box">
        <div class="address-line wrap-anywhere"><b>Name:</b> {{ $invoice->name }}</div>
        <div class="address-line wrap-anywhere"><b>Phone:</b> {{ $invoice->phone }}</div>
        <div class="address-line wrap-anywhere"><b>Address:</b> {{ $invoice->address }}</div>

        {{-- bottom-right mini logo --}}
        <div class="mini-logo">
            <img src="{{ asset('logo/SVG/Asset 2.svg') }}" alt="Mini Logo">

        </div>
    </div>

    {{-- COD BOX --}}
    <div class="cod-box">
        <div class="label">COD</div>
        <div class="amount">{{ $invoice->cod }}</div>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        <div class="icons">
            <img src="{{ asset('logo/SVG/Asset 3.svg') }}" style="height: 0.3643in;" alt="packaging-logo">
        </div>

        <div class="footer-mid">
            P: {{ now()->format('d/m/y h:ia') }}
        </div>

        <div class="courier">
            <img src="{{ asset('logo/SVG/Asset 4.svg') }}" alt="Steadfast">
            <div class="courier-site">www.steadfast.com.bd</div>
        </div>
    </div>

</div>
</body>
</html>
