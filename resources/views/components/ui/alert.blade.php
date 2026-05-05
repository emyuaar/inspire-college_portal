@props([
    'variant' => 'info', // success, error, warning, info
    'title' => null,
    'dismissible' => false,
])

@php
    $variants = [
        'success' => [
            'container' => 'bg-green-50 border-green-200',
            'icon' => 'text-green-500', 
            'text' => 'text-green-800',
            'title' => 'text-green-900',
            'icon_svg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
        ],
        'error' => [
            'container' => 'bg-red-50 border-red-200',
            'icon' => 'text-red-500',
            'text' => 'text-red-800',
            'title' => 'text-red-900',
            'icon_svg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />',
        ],
        'warning' => [
            'container' => 'bg-amber-50 border-amber-200',
            'icon' => 'text-amber-500',
            'text' => 'text-amber-800',
            'title' => 'text-amber-900',
            'icon_svg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
        ],
        'info' => [
            'container' => 'bg-blue-50 border-blue-200',
            'icon' => 'text-blue-500',
            'text' => 'text-blue-800',
            'title' => 'text-blue-900',
            'icon_svg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        ],
    ];

    $v = $variants[$variant];
@endphp

<div {{ $attributes->merge(['class' => "rounded-xl border p-4 flex items-start gap-3 {$v['container']}"]) }} x-data="{ show: true }" x-show="show">
    <svg class="w-5 h-5 mt-0.5 shrink-0 {{ $v['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        {!! $v['icon_svg'] !!}
    </svg>
    
    <div class="flex-1 min-w-0">
        @if($title)
            <h4 class="text-sm font-bold {{ $v['title'] }} mb-0.5">{{ $title }}</h4>
        @endif
        <div class="text-sm font-medium {{ $v['text'] }} leading-relaxed">
            {{ $slot }}
        </div>
    </div>

    @if($dismissible)
        <button @click="show = false" type="button" class="ml-auto -mx-1.5 -my-1.5 rounded-lg p-1.5 {{ $v['text'] }} hover:bg-white/20 focus:ring-2 focus:ring-offset-2 focus:ring-offset-{{ $variant }}-50 focus:ring-{{ $variant }}-600">
            <span class="sr-only">Dismiss</span>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>
