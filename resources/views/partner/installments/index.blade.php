@extends('layouts.partner')

@section('title', 'Manage Installments')
@section('active-page', 'installments')

@section('content')
<div class="space-y-6">

    {{-- PAGE HEADER --}}
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-bold text-slate-900 sm:truncate">Installment Management</h1>
            <p class="mt-1 text-sm text-slate-500">Track, review, and process learner installment payments.</p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4 gap-3">
             <div class="relative rounded-md shadow-sm max-w-xs">
                <form action="{{ route('partner.installments.index') }}" method="GET" class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-slate-400 text-xs"></i>
                    </div>
                    <input type="text" name="search" value="{{ $search }}"
                        class="focus:ring-ds-pink focus:border-ds-pink block w-full pl-9 pr-12 sm:text-xs border-slate-200 rounded-lg py-2" 
                        placeholder="Search learner...">
                    @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
                </form>
            </div>
        </div>
    </div>

    {{-- STATUS TABS --}}
    <div class="bg-white p-1 rounded-xl shadow-sm border border-slate-200 inline-flex flex-wrap gap-1">
        <a href="{{ route('partner.installments.index', ['status' => 'all', 'search' => $search]) }}" 
            class="px-4 py-2 rounded-lg text-xs font-bold transition-all {{ $status === 'all' ? 'bg-ds-navy text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' }}">
            All <span class="ml-1 opacity-60">({{ $counts['all'] }})</span>
        </a>
        <a href="{{ route('partner.installments.index', ['status' => 'overdue', 'search' => $search]) }}" 
            class="px-4 py-2 rounded-lg text-xs font-bold transition-all {{ $status === 'overdue' ? 'bg-rose-600 text-white shadow-md' : 'text-slate-500 hover:bg-rose-50 hover:text-rose-600' }}">
            Overdue <span class="ml-1 opacity-60">({{ $counts['overdue'] }})</span>
        </a>
        <a href="{{ route('partner.installments.index', ['status' => 'due_soon', 'search' => $search]) }}" 
            class="px-4 py-2 rounded-lg text-xs font-bold transition-all {{ $status === 'due_soon' ? 'bg-amber-500 text-white shadow-md' : 'text-slate-500 hover:bg-amber-50 hover:text-amber-600' }}">
            Due Soon <span class="ml-1 opacity-60">({{ $counts['due_soon'] }})</span>
        </a>
        <a href="{{ route('partner.installments.index', ['status' => 'pending', 'search' => $search]) }}" 
            class="px-4 py-2 rounded-lg text-xs font-bold transition-all {{ $status === 'pending' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-600' }}">
            Pending <span class="ml-1 opacity-60">({{ $counts['pending'] }})</span>
        </a>
        <a href="{{ route('partner.installments.index', ['status' => 'paid', 'search' => $search]) }}" 
            class="px-4 py-2 rounded-lg text-xs font-bold transition-all {{ $status === 'paid' ? 'bg-emerald-600 text-white shadow-md' : 'text-slate-500 hover:bg-emerald-50 hover:text-emerald-600' }}">
            Paid
        </a>
    </div>

    {{-- INSTALLMENTS TABLE --}}
    <x-ui.card class="border-slate-200 shadow-sm" padding="p-0">
        <div class="overflow-x-auto min-h-[400px]">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider">Learner Name</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider">Email</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider">Course</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider text-center">Payment Status</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider text-right">Pending Amount</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider">Next Due Date</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider">Grace Status</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($enrolments as $enrolment)
                        @php
                            $installments = $enrolment->partnerInstallments;
                            $totalAmount = $installments->sum('installment_amount');
                            $paidAmount = $installments->sum('paid_amount');
                            $pendingAmount = $totalAmount - $paidAmount;
                            
                            $unpaidInstallments = $installments->where('status', '!=', 'paid')->sortBy('due_date');
                            $nextDue = $unpaidInstallments->first();
                            $allPaid = $unpaidInstallments->isEmpty();
                            
                            $today = now()->startOfDay();
                            $graceText = '-';
                            $graceColor = 'text-slate-500';

                            if ($allPaid) {
                                $badgeColor = 'bg-emerald-100 text-emerald-800';
                                $badgeText = 'Paid';
                            } else {
                                $firstDue = $unpaidInstallments->first();
                                if ($firstDue->due_date < $today) {
                                    $badgeColor = 'bg-rose-100 text-rose-800';
                                    $badgeText = 'Overdue/Suspended';
                                } elseif ($firstDue->due_date <= $today->copy()->addDays(7)) {
                                    $badgeColor = 'bg-orange-100 text-orange-800';
                                    $badgeText = 'Due';
                                } else {
                                    $badgeColor = 'bg-yellow-100 text-yellow-800';
                                    $badgeText = 'Pending';
                                }
                            }

                            // If enrolment requires approval
                            if ($enrolment->status_id == 0 || $enrolment->status_id == null) {
                                $badgeColor = 'bg-purple-100 text-purple-800';
                                $badgeText = 'Awaiting Approval';
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-600 border border-slate-200 shrink-0">
                                        {{ substr($enrolment->learner->first_name ?? 'U', 0, 1) }}{{ substr($enrolment->learner->sur_name ?? 'N', 0, 1) }}
                                    </div>
                                    <div class="text-sm font-bold text-slate-900 truncate">
                                        {{ $enrolment->learner->first_name ?? 'Unknown' }} {{ $enrolment->learner->sur_name ?? 'Learner' }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600">
                                {{ $enrolment->learner->email_address ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-[11px] font-medium text-slate-700 truncate max-w-[200px]">
                                    {{ $enrolment->course->title ?? 'Course Not Found' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $badgeColor }}">
                                    {{ $badgeText }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-black {{ $pendingAmount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                    £{{ number_format($pendingAmount, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($nextDue)
                                    <span class="text-xs font-semibold text-slate-700">
                                        {{ $nextDue->due_date->format('d M, Y') }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs {{ $graceColor }}">{{ $graceText }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('partner.installments.show', $enrolment->id) }}" 
                                    class="inline-flex items-center justify-center px-3 py-1.5 border border-slate-200 text-[11px] font-bold rounded-lg text-slate-700 bg-white hover:bg-slate-50 shadow-sm transition-all">
                                    View Payments
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-24 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-slate-100">
                                        <i class="fa-solid fa-credit-card text-2xl text-slate-300"></i>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-900">No installments found</h3>
                                    <p class="text-xs text-slate-500 mt-1 max-w-[200px] mx-auto italic">
                                        We couldn't find any learner installments matching your current filter.
                                    </p>
                                    @if($search || $status !== 'all')
                                        <a href="{{ route('partner.installments.index') }}" class="mt-4 text-xs font-black text-ds-pink hover:underline">
                                            Clear all filters
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($enrolments->hasPages())
            <div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100">
                {{ $enrolments->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
