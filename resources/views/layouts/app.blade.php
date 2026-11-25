<!DOCTYPE html>
<html lang="en" class="{{ session('darkMode', false) ? 'dark' : '' }}">
<!-- Note: We added the 'dark' class to the <html> tag based on a session variable. 
     If you are using Alpine or Livewire to toggle dark mode, you may need to adjust this, 
     but this ensures the page background goes dark immediately on load. -->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Milk Collection')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body
    class="font-sans antialiased bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-slate-100 transition-colors duration-200">
    <div class="min-h-screen">
        @yield('content')
    </div>

    @livewireScripts

    <!-- Optional: If you want to ensure the <html> tag toggles when the Livewire component changes -->
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', ({ el, component }) => {
                if (el.querySelector('[x-data]')) {
                    // You might need to expose $darkMode to JS if it's not already, 
                    // but this handles the root class toggle if your Livewire component updates it.
                    const isDark = @json($darkMode ?? false);
                    if (isDark) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            });
        });
    </script>
</body>

</html>