@extends('prints.layout', ['title' => 'Print Logos'])

@section('content')

    <style>

        /* Reset */
        html, body {
            margin: 0;
            padding: 0;
        }

        /* EXACT LABEL SIZE */
        @media print {

            @page {
                size: 2in 2.99in;
                margin: 0;
            }

            html, body {
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Force exactly one label per page */
            .page {
                width: 2in;
                height: 2.99in;

                page-break-after: always;
                break-after: page;
            }

            .page:last-child {
                page-break-after: auto;
                break-after: auto;
            }

        }

        /* Preview */
        .page {

            width: 2in;
            height: 2.99in;

            margin: 0 auto;

            display: flex;
            align-items: center;
            justify-content: center;

            background: white;
        }

        /* Fit image inside label */
        .page img {

            max-width: 100%;
            max-height: 100%;

            width: auto;
            height: auto;

            display: block;

        }

    </style>


    <!-- Page 1 -->
    <div class="page">
        <img src="{{ asset('logo/thankyou.svg') }}">
    </div>


    <!-- Page 2 -->
    <div class="page">
        <img src="{{ asset('logo/combine-logo.svg') }}">
    </div>


@endsection
