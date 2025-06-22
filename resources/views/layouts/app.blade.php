{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/@heroicons/v2/24/outline/esm/index.js"></script>
    @stack('styles') {{-- Untuk CSS tambahan per halaman --}}
</head>
<body class="bg-gray-100">
    <div x-data="{ sidebarOpen: false, sidebarDesktop: true, sidebarCollapsed: false }" class="min-h-screen">

        <x-sidebar />

        <div :class="{'lg:pl-64': !sidebarCollapsed, 'lg:pl-20': sidebarCollapsed}">
            <x-top-nav>
                <x-slot name="header">
                    @yield('header')
                </x-slot>
            </x-top-nav>

            <x-mobile-sidebar />

            <main class="my-2">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts') {{-- Untuk JS tambahan per halaman --}}
</body>
</html>