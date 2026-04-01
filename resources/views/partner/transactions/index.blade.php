@extends('layouts.partner')

@section('title', 'Transaction History')
@section('active-page', 'transactions')

@section('content')
<div class="space-y-6">

    {{-- PAGE HEADER --}}
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-bold text-slate-900 sm:truncate">Payment History</h1>
            <p class="mt-1 text-sm text-slate-500">Review all payments made for your learners and download reports.</p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4 gap-3">
             <div class="relative rounded-md shadow-sm max-w-xs">
                <form action="{{ route('partner.transactions.index') }}" method="GET" class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-slate-400 text-xs"></i>
                    </div>
                    <input type="text" name="search" value="{{ $search }}"
                        class="focus:ring-ds-pink focus:border-ds-pink block w-full pl-9 pr-12 sm:text-xs border-slate-200 rounded-lg py-2" 
                        placeholder="Search learner...">
                    @if($type && $type !== 'all')<input type="hidden" name="type" value="{{ $type }}">@endif
                </form>
            </div>
            {{-- Optional: Export CSV Button could go here --}}
        </div>
    </div>

    {{-- FILTER TABS --}}
    <div class="bg-white p-1 rounded-xl shadow-sm border border-slate-200 inline-flex flex-wrap gap-1">
        <a href="{{ route('partner.transactions.index', ['type' => 'all', 'search' => $search]) }}" 
            class="px-4 py-2 rounded-lg text-xs font-bold transition-all {{ $type === 'all' ? 'bg-ds-navy text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' }}">
            All Payments
        </a>
        <a href="{{ route('partner.transactions.index', ['type' => 'deposits', 'search' => $search]) }}" 
            class="px-4 py-2 rounded-lg text-xs font-bold transition-all {{ $type === 'deposits' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-600' }}">
            Deposits
        </a>
        <a href="{{ route('partner.transactions.index', ['type' => 'installments', 'search' => $search]) }}" 
            class="px-4 py-2 rounded-lg text-xs font-bold transition-all {{ $type === 'installments' ? 'bg-amber-500 text-white shadow-md' : 'text-slate-500 hover:bg-amber-50 hover:text-amber-600' }}">
            Installments
        </a>
        <a href="{{ route('partner.transactions.index', ['type' => 'full', 'search' => $search]) }}" 
            class="px-4 py-2 rounded-lg text-xs font-bold transition-all {{ $type === 'full' ? 'bg-emerald-600 text-white shadow-md' : 'text-slate-500 hover:bg-emerald-50 hover:text-emerald-600' }}">
            Full Payments
        </a>
    </div>

    {{-- TRANSACTIONS TABLE --}}
    <x-ui.card class="border-slate-200 shadow-sm" padding="p-0">
        <div class="overflow-x-auto min-h-[400px]">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider">Date & Ref</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider">Learner Name</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider">Course / Detail</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider text-right">Amount</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider text-center">Type</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="text-xs font-bold text-slate-900">{{ $tx->date->format('d M, Y') }}</div>
                                <div class="text-[9px] text-slate-400 font-medium uppercase tracking-tight truncate max-w-[120px]" title="{{ $tx->reference }}">#{{ $tx->reference }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-slate-900">
                                    {{ $tx->learner->first_name }} {{ $tx->learner->sur_name }}
                                </div>
                                <div class="text-[10px] text-slate-500">{{ $tx->learner->email_address }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-medium text-slate-800 line-clamp-1" title="{{ $tx->course_title }}">
                                    {{ $tx->course_title }}
                                </div>
                                <div class="text-[9px] text-green-600 font-bold uppercase tracking-widest mt-0.5">Payment Successful</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-black text-slate-900">£{{ number_format($tx->amount, 2) }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if(str_contains($tx->type, 'Deposit'))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100">DEPOSIT</span>
                                @elseif(str_contains($tx->type, 'Installment'))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-100">{{ strtoupper($tx->type) }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">FULL PAID</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2 text-xs">
                                    <a href="{{ route('partner.learners.show', $tx->learner_id) }}" 
                                       class="p-2 text-slate-400 hover:text-ds-navy transition-colors" title="View Learner">
                                        <i class="fa-solid fa-user"></i>
                                    </a>
                                    <a href="{{ route('partner.learners.show', $tx->learner_id) }}#financial-section" 
                                       class="p-2 text-slate-400 hover:text-ds-pink transition-colors" title="View Context">
                                        <i class="fa-solid fa-file-invoice-dollar"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="h-16 w-16 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                        <i class="fa-solid fa-receipt text-slate-200 text-2xl"></i>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-800">No transactions found</h3>
                                    <p class="text-xs text-slate-400 mt-1 max-w-[250px]">Try adjusting your filters or search terms for your learners' payment history.</p>
                                    @if($search || $type !== 'all')
                                        <a href="{{ route('partner.transactions.index') }}" class="mt-4 text-xs font-bold text-ds-pink hover:underline">Clear all filters</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- PAGINATION --}}
        @if($transactions->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 rounded-b-xl">
                {{ $transactions->links() }}
            </div>
        @endif
    </x-ui.card>
</div>

<style>
    /* Custom pagination styling if needed to match theme */
    .pagination {
        display: flex;
        gap: 0.25rem;
    }
</style>
@endsection
