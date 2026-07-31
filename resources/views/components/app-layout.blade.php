<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Inspire College Portal')</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Scripts & Styles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-900 antialiased lg:flex lg:overflow-hidden">

    {{-- Sidebar Component --}}
    <x-layout.sidebar :active="$attributes->get('active-page')" />

    {{-- Main Content Area --}}
    <div class="flex flex-col min-h-screen lg:min-h-0 lg:h-full lg:flex-1 lg:overflow-hidden relative">
        
        {{-- Topbar Component --}}
        <x-layout.topbar :title="$attributes->get('page-title')" />

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
                
                {{ $slot }}
            </div>
        </main>
    </div>

</body>
</html>
