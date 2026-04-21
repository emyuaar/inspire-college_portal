@props([
    'variant' => 'neutral', // success, warning, error, info, neutral, brand
    'size' => 'md', // sm, md
    'rounded' => 'full', // full, md
])

@php
    $sizes = [
        'sm' => 'px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold',
        'md' => 'px-2.5 py-0.5 text-xs font-semibold',
    ];
    
    $rounds = [
        'full' => 'rounded-full',
        'md' => 'rounded-md',
    ];

    $variantClass = \App\Services\Ui\UiVariants::badge($variant);
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $roundedClass = $rounds[$rounded] ?? $rounds['full'];

    $classes = 'inline-flex items-center justify-center ' . 
               $variantClass . ' ' .
               $sizeClass . ' ' .
               $roundedClass;
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
