<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Hóa đơn · Homi')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-50 font-sans text-slate-800 dark:bg-slate-950 dark:text-slate-100">
    <main class="mx-auto w-[min(860px,calc(100%-32px))] py-8">
        @yield('content')
    </main>
</body>

</html>
