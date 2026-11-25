<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Milk Collection')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .dark-theme {
            background: #0f172a;
            color: #f8fafc;
        }

        .dark-theme .bg-white {
            background-color: #0f172a !important;
        }

        .dark-theme .bg-slate-50 {
            background-color: #14243d !important;
        }

        .dark-theme .text-slate-900,
        .dark-theme .text-slate-800 {
            color: #f8fafc !important;
        }

        .dark-theme .text-slate-500,
        .dark-theme .text-slate-600,
        .dark-theme .text-slate-700 {
            color: #cbd5e1 !important;
        }

        .dark-theme .border-slate-200 {
            border-color: #475569 !important;
        }

        .dark-theme .bg-emerald-50 {
            background-color: #064e3b !important;
        }

        .dark-theme .text-emerald-600 {
            color: #22c55e !important;
        }

        .dark-theme .bg-amber-100 {
            background-color: #78350f !important;
        }

        .dark-theme .text-amber-700 {
            color: #f59e0b !important;
        }

        .dark-theme .bg-slate-100 {
            background-color: #0f172a !important;
        }

        .dark-theme input,
        .dark-theme textarea,
        .dark-theme select {
            background: #0f172a !important;
            color: #f8fafc !important;
            border-color: #475569 !important;
        }

        .dark-theme .rounded-2xl {
            border-color: #475569 !important;
        }
    </style>
    @livewireStyles
</head>

<body class="font-sans antialiased bg-slate-50 text-slate-900">
    <div class="min-h-screen">
        @yield('content')
    </div>
    @livewireScripts
</body>

</html>