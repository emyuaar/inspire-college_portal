@props([
    'variant' => 'primary', // primary, secondary, outline, danger, ghost
    'size' => 'md', // sm, md, lg
    'fullWidth' => false,
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'position' => 'left', // icon position
])

@php
    $baseClasses = 'inline-flex items-center justify-center rounded-full font-bold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

    $variants = [
        'primary' => 'bg-ds-navy text-white hover:bg-[#00203a] focus:ring-ds-navy shadow-lg shadow-blue-900/10 active:scale-[0.98]',
        'secondary' => 'bg-ds-pink text-white hover:bg-[#8f165a] focus:ring-ds-pink shadow-lg shadow-pink-900/10 active:scale-[0.98]',
        'outline' => 'bg-white border-2 border-slate-200 text-slate-700 hover:border-ds-navy hover:text-ds-navy focus:ring-ds-navy active:bg-slate-50',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-600 shadow-md shadow-red-900/10',
        'ghost' => 'bg-transparent text-slate-600 hover:text-ds-navy hover:bg-slate-100',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-5 py-2.5 text-sm',
        'lg' => 'px-8 py-3.5 text-base',
    ];

    $classes = $baseClasses . ' ' . 
               $variants[$variant] . ' ' . 
               $sizes[$size] . ' ' . 
               ($fullWidth ? 'w-full' : '');
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon && $position === 'left')
            <span class="w-4 h-4 mr-2 flex items-center justify-center">{{ $icon }}</span>
        @endif
        
        {{ $slot }}

        @if($icon && $position === 'right')
            <span class="w-4 h-4 ml-2 flex items-center justify-center">{{ $icon }}</span>
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon && $position === 'left')
            {{-- Assuming usage of heroicons or similar, can be swapped for svg slot --}}
            <span class="mr-2">{{ $icon }}</span>
        @endif
        
        {{ $slot }}

        @if($icon && $position === 'right')
           <span class="ml-2">{{ $icon }}</span>
        @endif
    </button>
@endif
