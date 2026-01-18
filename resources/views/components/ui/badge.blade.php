@props([
    'variant' => 'neutral', // success, warning, error, info, neutral, brand
    'size' => 'md', // sm, md
    'rounded' => 'full', // full, md
])

@php
    $variants = [
        'success' => 'bg-green-100 text-green-700 border border-green-200',
        'warning' => 'bg-amber-50 text-amber-700 border border-amber-200',
        'error' => 'bg-red-50 text-red-700 border border-red-200',
        'info' => 'bg-blue-50 text-blue-700 border border-blue-200',
        'neutral' => 'bg-slate-100 text-slate-700 border border-slate-200',
        'brand' => 'bg-ds-navy/10 text-ds-navy border border-ds-navy/20',
    ];

    $sizes = [
        'sm' => 'px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold',
        'md' => 'px-2.5 py-0.5 text-xs font-semibold',
    ];
    
    $rounds = [
        'full' => 'rounded-full',
        'md' => 'rounded-md',
    ];

    $classes = 'inline-flex items-center justify-center ' . 
               $variants[$variant] . ' ' . 
               $sizes[$size] . ' ' . 
               $rounds[$rounded];
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
