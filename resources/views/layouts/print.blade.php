<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Hóa đơn · Homi')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /*
         * Hóa đơn in ra giấy trước đây không có rule @media print riêng —
         * chỉ ẩn nút back/print bằng "print:hidden", còn lại giữ nguyên nền
         * xám/card/shadow của bản xem trên web, gây tốn mực và tràn trang
         * khi in thật. Rule dưới đây đưa về bố cục A4 gọn, trắng đen, không
         * bóng đổ/bo góc, không vỡ trang giữa 1 dòng bảng.
         */
        @media print {
            @page {
                size: A4;
                margin: 14mm 12mm;
            }

            html, body {
                background: #fff !important;
                color: #000 !important;
                font-size: 12px;
            }

            .print\:hidden {
                display: none !important;
            }

            main {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            #invoice-document {
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            thead {
                display: table-header-group;
            }

            tr, .info-item {
                page-break-inside: avoid;
            }

            a[href]::after {
                content: '';
            }
        }
    </style>
</head>

<body class="bg-slate-50 font-sans text-slate-800 dark:bg-slate-950 dark:text-slate-100">
    <main class="mx-auto w-[min(860px,calc(100%-32px))] py-8">
        @yield('content')
    </main>
</body>

</html>
