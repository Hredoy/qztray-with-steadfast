<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* DomPDF friendly */
        @page { margin: 0; }
        html, body { margin: 0; padding: 0; }

        .page {
            width: 144pt;      /* 2.00in */
            height: 215.28pt;  /* 2.99in */
            margin: 0;
            padding: 0;
            overflow: hidden;
            page-break-after: always;
        }
        .page:last-child { page-break-after: auto; }

        /* Fit image without cropping */
        .img-wrap {
            width: 144pt;
            height: 215.28pt;
            text-align: center;
        }

        img {
            max-width: 144pt;
            max-height: 215.28pt;
            width: auto;
            height: auto;
            display: block;
            margin: 0 auto;
        }
    </style>
</head>
<body>

<!-- Page 1 -->
<div class="page">
    <div class="img-wrap">
        <img src="file://{{ $img1 }}" alt="Thank You">
    </div>
</div>

<!-- Page 2 -->
<div class="page">
    <div class="img-wrap">
        <img src="file://{{ $img2 }}" alt="Combine Logo">
    </div>
</div>

</body>
</html>
