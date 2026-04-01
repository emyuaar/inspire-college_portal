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
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider">Learner & Course</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider text-center">Type/No</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-[10px] uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($installments as $installment)
                        @php
                            $urgency = $installment->urgency;
                            $isOverdue = $urgency === 'overdue';
                            $isDueSoon = $urgency === 'due_soon';
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors {{ $isOverdue ? 'bg-rose-50/30' : '' }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-600 border border-slate-200 shrink-0">
                                        {{ substr($installment->learner->first_name, 0, 1) }}{{ substr($installment->learner->sur_name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-bold text-slate-900 truncate">
                                            {{ $installment->learner->first_name }} {{ $installment->learner->sur_name }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 font-medium truncate max-w-[200px]">
                                            {{ $installment->course->title ?? 'Course Not Found' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($installment->installment_no == 0)
                                    <div class="text-[11px] font-bold text-ds-navy bg-blue-50 px-2 py-0.5 rounded-full inline-block">Deposit</div>
                                @else
                                    <div class="text-[11px] font-bold text-slate-600">Installment #{{ $installment->installment_no }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-semibold {{ $isOverdue ? 'text-rose-600' : 'text-slate-700' }}">
                                        {{ $installment->due_date->format('d M, Y') }}
                                    </span>
                                    @if($isOverdue)
                                        <span class="text-[10px] font-bold text-rose-500 uppercase tracking-tighter">
                                            <i class="fa-solid fa-triangle-exclamation mr-1"></i> Overdue
                                        </span>
                                    @elseif($isDueSoon)
                                        <span class="text-[10px] font-bold text-amber-500 uppercase tracking-tighter">
                                            Due Soon
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-slate-900">£{{ number_format($installment->installment_amount, 2) }}</span>
                                    @if($installment->status === 'partial')
                                        @php $balance = $installment->installment_amount - $installment->paid_amount; @endphp
                                        <span class="text-[10px] text-rose-600 font-bold">
                                            Balance: £{{ number_format($balance, 2) }}
                                        </span>
                                    @elseif($installment->status === 'paid')
                                        <span class="text-[10px] text-emerald-600 font-bold">
                                            Fully Paid
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($installment->status === 'paid')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        <i class="fa-solid fa-check-circle mr-1 text-[10px]"></i> Paid
                                    </span>
                                @elseif($installment->status === 'partial')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                        <i class="fa-solid fa-spinner mr-1 text-[10px]"></i> Partial
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800">
                                        Pending
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2" x-data="{ open: false }">
                                    @if($installment->status !== 'paid')
                                        <a href="{{ route('partner.installments.checkout', $installment->id) }}" 
                                            class="inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-[11px] font-bold rounded-lg text-white bg-ds-pink hover:bg-pink-700 shadow-sm transition-all shadow-pink-200">
                                            Pay Now
                                        </a>
                                    @endif
                                    
                                    <div class="relative">
                                        <button @click="open = !open" @click.away="open = false" 
                                            class="p-2 text-slate-400 hover:text-slate-600 transition-colors">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        
                                        <div x-show="open" x-transition 
                                            class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-100 z-50 py-1 overflow-hidden text-left">
                                            <a href="{{ route('partner.learners.show', $installment->learner_id) }}" 
                                                class="flex items-center gap-2 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                                                <i class="fa-solid fa-user w-4 opacity-50"></i> View Learner
                                            </a>
                                            @if($installment->receipt_path)
                                            <a href="{{ asset('storage/' . $installment->receipt_path) }}" target="_blank"
                                                class="flex items-center gap-2 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                                                <i class="fa-solid fa-file-invoice w-4 opacity-50"></i> View Proof
                                            </a>
                                            @elseif($installment->status !== 'paid')
                                            <button type="button" @click="$dispatch('open-payment-modal', {id: {{ $installment->id }}})"
                                                class="w-full flex items-center gap-2 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors text-left">
                                                <i class="fa-solid fa-receipt w-4 opacity-50"></i> Record Proof
                                            </button>
                                            @endif
                                            <div class="border-t border-slate-100 my-1"></div>
                                            <a href="{{ route('partner.learners.show', $installment->learner_id) }}#financial-section" 
                                                class="flex items-center gap-2 px-4 py-2 text-xs font-bold text-blue-600 hover:bg-blue-50 transition-colors">
                                                <i class="fa-solid fa-wallet w-4"></i> Financial Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-24 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-slate-100">
                                        <i class="fa-solid fa-credit-card text-2xl text-slate-300"></i>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-900">No installments found</h3>
                                    <p class="text-xs text-slate-500 mt-1 max-w-[200px] mx-auto italic">
                                        We couldn't find any installments matching your current filter.
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
        
        @if($installments->hasPages())
            <div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100">
                {{ $installments->links() }}
            </div>
        @endif
    </x-ui.card>

    {{-- MODALS CONTAINER --}}
    @foreach($installments as $inst)
        @if($inst->status != 'paid')
            <div class="modal fade fixed top-0 left-0 hidden w-full h-full outline-none overflow-x-hidden overflow-y-auto z-[60]"
                id="payModal-{{ $inst->id }}" tabindex="-1" aria-hidden="true" 
                x-data="{}" @open-payment-modal.window="if($event.detail.id == {{ $inst->id }}) new bootstrap.Modal($el).show()">
                <div class="modal-dialog relative w-full pointer-events-none mx-auto mt-20 max-w-sm sm:max-w-md">
                    <div
                        class="modal-content border-none shadow-2xl relative flex flex-col w-full pointer-events-auto bg-white bg-clip-padding rounded-2xl outline-none text-current transform transition-all border border-slate-100">
                        <form action="{{ route('partner.installments.pay', $inst->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div
                                class="modal-header flex flex-shrink-0 items-center justify-between p-6 border-b border-gray-100 rounded-t-2xl bg-slate-50/50">
                                <div>
                                    <h5 class="text-base font-bold text-slate-800">Record Proof of Payment</h5>
                                    <p class="text-xs text-slate-500 mt-0.5">Manually record learner payment</p>
                                </div>
                                <button type="button"
                                    class="text-slate-400 hover:text-slate-600 transition-colors"
                                    data-bs-dismiss="modal" aria-label="Close">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <div class="modal-body relative p-6 text-left space-y-5">
                                <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                                    <div class="text-[10px] text-blue-500 font-bold uppercase tracking-wider mb-1">Target Installment</div>
                                    <div class="font-bold text-slate-900 text-sm mb-0.5">{{ $inst->course->title ?? 'Course' }}</div>
                                    <div class="flex items-center justify-between mt-2">
                                        <div class="text-xs text-slate-600 font-medium">
                                            {{ $inst->installment_no == 0 ? 'Deposit Payment' : 'Installment #' . $inst->installment_no }}
                                        </div>
                                        <div class="text-sm font-black text-blue-700">£{{ number_format($inst->installment_amount - $inst->paid_amount, 2) }}</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold mb-1.5 text-slate-600">Amount Paid (£)</label>
                                        <input type="number" step="0.01" name="amount"
                                            class="w-full text-sm border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-ds-pink/20 focus:border-ds-pink transition-all font-medium"
                                            value="{{ $inst->installment_amount - $inst->paid_amount }}" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1.5 text-slate-600">Date Paid</label>
                                        <input type="date" name="payment_date"
                                            class="w-full text-sm border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-ds-pink/20 focus:border-ds-pink transition-all text-slate-600"
                                            value="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold mb-1.5 text-slate-600">Reference / Note</label>
                                    <input type="text" name="payment_reference"
                                        class="w-full text-sm border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-ds-pink/20 focus:border-ds-pink transition-all placeholder:text-slate-300"
                                        placeholder="e.g. Bank Ref, Cash, etc.">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold mb-1.5 text-slate-600">Upload Receipt Proof</label>
                                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-200 border-dashed rounded-xl hover:border-slate-300 transition-colors cursor-pointer group relative">
                                        <div class="space-y-1 text-center">
                                            <i class="fa-solid fa-cloud-arrow-up text-3xl text-slate-300 mb-2 group-hover:text-slate-400 transition-colors"></i>
                                            <div class="flex text-xs text-slate-600">
                                                <span class="font-bold text-ds-pink hover:text-pink-700">Upload a file</span>
                                                <p class="pl-1">or drag and drop</p>
                                            </div>
                                            <p class="text-[10px] text-slate-500">PDF, JPG, PNG up to 5MB</p>
                                        </div>
                                        <input type="file" name="receipt" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                    </div>
                                </div>
                            </div>
                            <div
                                class="modal-footer flex flex-shrink-0 flex-wrap items-center justify-end p-6 border-t border-gray-100 rounded-b-2xl gap-3 bg-slate-50/30">
                                <button type="button"
                                    class="px-4 py-2 bg-white border border-slate-200 text-slate-600 font-bold text-xs rounded-lg shadow-sm hover:bg-slate-50 hover:text-slate-800 transition-all"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit"
                                    class="px-6 py-2 bg-ds-navy text-white font-bold text-xs rounded-lg shadow-lg hover:bg-slate-900 transition-all transform active:scale-95 shadow-slate-200">
                                    Finalize Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

</div>
@endsection
