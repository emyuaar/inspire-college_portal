@extends('layouts.partner')

@section('title', 'Review Pending Plans')
@section('active-page', 'pending_plans')

@section('content')
<div class="space-y-6">

    {{-- HEADER CARD --}}
    <x-ui.card class="bg-gradient-to-r from-ds-navy to-[#0F4C81] text-white border-none overflow-hidden relative">
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-ds-pink/20 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 rounded-full bg-white/10 text-[10px] font-bold uppercase tracking-wider text-white/90 border border-white/10">
                        Attention Required
                    </span>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold mb-2">
                    Review Pending Plans
                </h1>
                <p class="text-blue-100/80 text-sm max-w-xl leading-relaxed">
                    Select payment plans (Full or Installment) for recently created enrolments to proceed to payment.
                </p>
            </div>
            
            <div class="flex items-center gap-3 shrink-0">
                <div class="bg-white/10 backdrop-blur-md px-4 py-2 rounded-xl border border-white/10">
                    <div class="text-[10px] text-blue-200 font-bold uppercase tracking-widest leading-none mb-1">Total Pending</div>
                    <div class="text-2xl font-black leading-none">{{ $enrolments->count() }}</div>
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- PENDING PLANS LIST --}}
    <x-ui.card class="border-slate-200 shadow-sm" padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-200">
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider">Learner</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider">Course</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider">Enrolled On</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider text-right">Status</th>
                        <th class="px-6 py-4 text-right font-bold text-slate-600 text-xs uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($enrolments as $enrolment)
                        <tr class="group hover:bg-slate-50/80 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 shrink-0 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 font-bold text-sm border border-slate-200 shadow-sm">
                                        {{ substr($enrolment->learner->first_name ?? '?', 0, 1) }}{{ substr($enrolment->learner->sur_name ?? '?', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm">{{ $enrolment->learner->first_name }} {{ $enrolment->learner->sur_name }}</div>
                                        <div class="text-[10px] text-slate-500 font-medium uppercase tracking-tight">{{ $enrolment->learner->email_address }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-800 text-sm">{{ $enrolment->course->title }}</div>
                                <div class="text-[10px] text-slate-400 font-medium truncate max-w-[200px]">ID: #{{ $enrolment->id }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-slate-600 font-medium italic">
                                    {{ $enrolment->created_at->format('d M Y, H:i') }}
                                </span>
                            </td>
                            @php
                                $statusName = strtolower($enrolment->status->status ?? 'Unknown');
                                $statusVariant = match($statusName) {
                                    'active', 'approved', 'paid' => 'success',
                                    'pending-payment' => 'warning',
                                    'pending-plan', 'pending' => 'brand',
                                    'denied' => 'error',
                                    default => 'neutral'
                                };
                            @endphp
                            <td class="px-6 py-4 text-right">
                                <x-ui.badge variant="{{ $statusVariant }}" size="sm">
                                    {{ str_replace('-', ' ', ucfirst($statusName)) }}
                                </x-ui.badge>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <x-ui.button variant="brand" size="sm" href="{{ route('partner.enrolments.choose_plan', $enrolment->id) }}">
                                    Review Plan
                                </x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="mx-auto w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                    <i class="fa-solid fa-file-circle-check text-2xl text-slate-300"></i>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900">All caught up!</h3>
                                <p class="mt-1 text-xs text-slate-500">No enrolments are currently awaiting plan selection.</p>
                                <div class="mt-4">
                                    <x-ui.button variant="ghost" class="border border-slate-200" href="{{ route('partner.dashboard') }}">
                                        Back to Dashboard
                                    </x-ui.button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
@endsection
