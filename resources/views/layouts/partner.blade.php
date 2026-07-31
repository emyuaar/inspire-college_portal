<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Partner Portal') - Inspire College</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Bootstrap 5 for Modals (Legacy Requirement maintained) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Scripts & Styles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="h-full bg-slate-50 text-slate-900 antialiased lg:flex lg:overflow-hidden">

    {{-- Sidebar Component --}}
    <x-layout.partner-sidebar :active="trim($__env->yieldContent('active-page', ''))" />

    {{-- Main Content Area --}}
    <div class="flex flex-col min-h-screen lg:min-h-0 lg:h-full lg:flex-1 lg:overflow-hidden relative">

        {{-- Topbar Component --}}
        <x-layout.topbar :title="trim($__env->yieldContent('title', 'Partner Portal'))" userRole="Partner" />

        {{-- Scrollable Page Content --}}
        <main class="flex-1 p-4 md:p-6 lg:p-8 lg:overflow-y-auto scroll-smooth">
            <div class="max-w-7xl mx-auto space-y-6 pb-12">
                @if(session('success'))
                    <x-ui.alert variant="success" dismissible title="Success">
                        {{ session('success') }}
                    </x-ui.alert>
                @endif

                @if(session('error'))
                    <x-ui.alert variant="error" dismissible title="Error">
                        {{ session('error') }}
                    </x-ui.alert>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>

</html>
