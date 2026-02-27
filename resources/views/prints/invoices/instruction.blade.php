@extends('prints.layout', ['title' => 'Print Instruction'])

@section('content')
    <style>
        /* ✅ SCREEN (modal iframe preview) */
        html, body { margin: 0; padding: 0; }

        .receipt{
            width: 100%;              /* fit inside iframe */
            max-width: 100%;
            margin: 0 auto;
            padding: 0;
        }

        .box{
            width: 100%;
            padding: 10px;
            margin: 0;
            box-sizing: border-box;
            border: 0;
        }

        hr { margin: 6px 0; }
        .title { font-size: 10px; font-weight: 700; margin: 0 0 6px; }
        .meta  { font-size: 9px; color: #666; margin: 0 0 8px; }

        .body{
            width: 7.5in;
            font-size: 7px;
            line-height: 1.35;

            white-space: pre-wrap;      /* keep new lines */

            /* FORCE BREAK WHEN OVERFLOW */
            overflow-wrap: anywhere;    /* strongest wrap */
            word-break: break-all;      /* break words if needed */

            max-width: 100%;
        }

        /* ✅ PRINT (POS receipt width) */
        @media print {
            html, body { margin: 0 !important; padding: 0 !important; }

            @page {
                size: 7.5in auto;        /* fixed width, auto height */
                margin: 0;
            }

            .body{
                font-size: 7px;
                line-height: 1.35;

                white-space: pre-wrap;      /* keep new lines */

                /* FORCE BREAK WHEN OVERFLOW */
                overflow-wrap: anywhere;    /* strongest wrap */
                word-break: break-all;      /* break words if needed */

                max-width: 100%;
            }

            .receipt{
                width: 7.5in;            /* only in print */
                max-width: 7.5in;
            }

            /* avoid splitting blocks */
            .receipt, .box{
                break-inside: avoid;
                page-break-inside: avoid;
            }

            /* force black text in print */
            * { color: #000 !important; }
        }
    </style>

    <div class="receipt">
        <div class="box">
            <div class="title">Instruction</div>
            <div class="meta">Invoice: {{ $invoice->invoice }} | Phone: {{ $invoice->phone }}</div>
            <hr>
            <div class="body">{{ $invoice->instruction ?: 'No instruction found.' }}</div>
        </div>
    </div>
@endsection
