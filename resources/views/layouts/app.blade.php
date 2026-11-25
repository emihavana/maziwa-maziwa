<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Milk Collection')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* We changed .dark-theme to .dark so Tailwind's dark: classes work natively */
        .dark {
            background: #0f172a;
            color: #f8fafc;
        }

        /* Force background colors to be dark when the 'dark' class is on <html> */
        .dark .bg-white {
            background-color: #1e293b !important;
            /* This is slate-800 */
        }

        .dark .bg-slate-50 {
            background-color: #0f172a !important;
            /* This is slate-900 */
        }

        .dark .text-slate-900,
        .dark .text-slate-800 {
            color: #f8fafc !important;
        }

        .dark .text-slate-500,
        .dark .text-slate-600,
        .dark .text-slate-700 {
            color: #cbd5e1 !important;
        }

        .dark .border-slate-200 {
            border-color: #334155 !important;
            /* slate-700 */
        }

        .dark .bg-emerald-50 {
            background-color: #064e3b !important;
        }

        .dark .bg-amber-100 {
            background-color: #78350f !important;
        }

        .dark input,
        .dark textarea,
        .dark select {
            background: #0f172a !important;
            color: #f8fafc !important;
            border-color: #475569 !important;
        }
    </style>
    @livewireStyles
</head>

<body class="font-sans antialiased bg-slate-50 text-slate-900 transition-colors duration-300">
    <div class="min-h-screen">
        @yield('content')
    </div>
    @livewireScripts
</body>

</html>