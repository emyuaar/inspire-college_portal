@extends('layouts.partner')

@section('title', 'Notifications')
@section('active-page', 'notifications')

@section('content')
<div class="space-y-6 pb-12">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Notifications</h1>
            <p class="text-slate-500 mt-2 font-medium">
                Updates and alerts for your learners, payments, and enrolments.
            </p>
        </div>

        @if($notifications->where('read_at', null)->count())
        <form method="POST" action="{{ route('partner.notifications.read_all') }}">
            @csrf
            <button type="submit"
                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 shadow-sm transition-all">
                <i class="fa-solid fa-check-double text-slate-400"></i>
                Mark All as Read
            </button>
        </form>
        @endif
    </div>

    {{-- Notification List --}}
    @if($notifications->count())
        <div class="space-y-3">
            @foreach($notifications as $n)
                @php
                    $colors = $n->colorClasses();
                @endphp
                <a href="{{ route('partner.notifications.read', $n->id) }}"
                   class="group flex items-start gap-5 p-5 bg-white rounded-2xl border {{ $n->isUnread() ? 'border-ds-pink/20 shadow-md shadow-blue-50' : 'border-slate-100 shadow-sm' }} hover:shadow-lg transition-all duration-200">

                    {{-- Icon --}}
                    <div class="shrink-0 w-12 h-12 rounded-2xl {{ $colors['bg'] }} flex items-center justify-center">
                        <i class="{{ $n->iconClass() }} text-lg {{ $colors['text'] }}"></i>
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-black text-slate-800 leading-tight flex items-center gap-2">
                                    {{ $n->title }}
                                    @if($n->isUnread())
                                        <span class="inline-block w-2 h-2 rounded-full bg-ds-pink"></span>
                                    @endif
                                </p>
                                <p class="text-sm text-slate-500 mt-1 leading-relaxed">{{ $n->body }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-slate-400 whitespace-nowrap mt-0.5">{{ $n->relativeTime() }}</span>
                        </div>

                        @if($n->action_label)
                        <div class="mt-3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 group-hover:bg-slate-200 text-xs font-bold text-slate-600 rounded-lg transition-colors">
                                {{ $n->action_label }}
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </span>
                        </div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $notifications->links() }}
        </div>

    @else
        {{-- Empty State --}}
        <div class="text-center py-24 bg-white rounded-[2rem] border border-slate-100 shadow-sm flex flex-col items-center">
            <div class="w-24 h-24 rounded-full bg-slate-50 flex items-center justify-center mb-6">
                <i class="fa-solid fa-bell-slash text-4xl text-slate-200"></i>
            </div>
            <h3 class="text-2xl font-black text-slate-900">All Clear</h3>
            <p class="text-slate-500 max-w-sm mx-auto mt-3 font-medium">
                No notifications right now. We'll alert you when something needs your attention.
            </p>
        </div>
    @endif

</div>
@endsection
