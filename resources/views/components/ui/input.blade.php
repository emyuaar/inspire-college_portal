@props([
    'disabled' => false,
    'label' => null,
    'id' => null,
    'name' => null,
    'type' => 'text',
    'helper' => null,
    'error' => null,
])

@php
    $id = $id ?? $name ?? Str::random(8);
    $baseClasses = 'block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-ds-pink focus:ring-ds-pink transition-all text-sm shadow-sm disabled:opacity-60 disabled:cursor-not-allowed';
    
    if ($error) {
        $baseClasses .= ' border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50';
    }
@endphp

<div {{ $attributes->except(['class', 'value']) }}>
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-semibold text-slate-700 mb-1.5">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <input 
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            {{ $disabled ? 'disabled' : '' }}
            {{ $attributes->only(['class', 'value', 'placeholder', 'required', 'autofocus', 'autocomplete', 'min', 'max', 'step']) }}
            class="{{ $baseClasses }} {{ $attributes->get('class') }}"
            style="min-height: 48px;"
        />
        
        @if($error)
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
        @endif
    </div>

    @if($error)
        <p class="mt-1.5 text-xs text-red-600 font-medium">{{ $error }}</p>
    @elseif($helper)
        <p class="mt-1.5 text-xs text-slate-500">{{ $helper }}</p>
    @endif
</div>
