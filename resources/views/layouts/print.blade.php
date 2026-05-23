<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <style>
        @font-face {
            font-family: 'Noto Nastaliq Urdu';
            src: local('Noto Nastaliq Urdu'), local('Jameel Noori Nastaleeq'), serif;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #fff;
            color: #000;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            -webkit-print-color-adjust: exact;
        }

        .print-container {
            margin: 0 auto;
        }

        /* Narrow Slip Format (80mm) */
        .format-slip {
            width: 80mm;
            padding: 3mm;
        }

        /* A5 Format */
        .format-a5 {
            width: 148mm;
            padding: 8mm;
        }

        @media print {
            .no-print { display: none !important; }
            body { background: none; }
            @page { margin: 0; }
        }

        @yield('extra_css')
    </style>
</head>
<body onload="window.print()">
    <div class="print-container {{ $format === 'a5' ? 'format-a5' : 'format-slip' }}">
        @yield('content')
    </div>
</body>
</html>
