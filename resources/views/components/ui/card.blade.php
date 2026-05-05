@props([
    'padding' => 'p-6',
    'title' => null,
    'subtitle' => null,
    'actions' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden']) }}>
    @if($title || $actions)
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                @if($title)
                    <h3 class="font-bold text-slate-800 text-lg">{{ $title }}</h3>
                @endif
                @if($subtitle)
                    <p class="text-sm text-slate-500 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            
            @if($actions)
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>
</div>
