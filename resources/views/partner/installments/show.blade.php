@extends('layouts.partner')

@section('title', 'Learner Payment Details')
@section('active-page', 'installments')

@section('content')
<div class="space-y-8 animate-in fade-in duration-700">
    {{-- Header Section --}}
    <div class="md:flex md:items-end md:justify-between bg-white p-8 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
        {{-- Decorative Background Element --}}
        <div class="absolute top-0 right-0 -mt-8 -mr-8 w-32 h-32 bg-indigo-50 rounded-full opacity-50 group-hover:scale-110 transition-transform duration-700"></div>
        
        <div class="flex-1 min-w-0 relative">
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2 text-xs font-bold text-slate-400 uppercase tracking-widest">
                    <li><a href="{{ route('partner.dashboard') }}" class="hover:text-ds-pink transition-colors">Dashboard</a></li>
                    <li><i class="fa-solid fa-chevron-right text-[8px] mx-1"></i></li>
                    <li><a href="{{ route('partner.installments.index') }}" class="hover:text-ds-pink transition-colors">Installments</a></li>
                    <li><i class="fa-solid fa-chevron-right text-[8px] mx-1"></i></li>
                    <li class="text-slate-900">Details</li>
                </ol>
            </nav>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Learner Payment Details</h1>
            <div class="mt-2 flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-ds-pink/10 flex items-center justify-center text-ds-pink font-black text-sm">
                    {{ substr($enrolment->learner->first_name ?? 'L', 0, 1) }}{{ substr($enrolment->learner->sur_name ?? '', 0, 1) }}
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-700">
                        {{ $enrolment->learner->first_name ?? 'Unknown' }} {{ $enrolment->learner->sur_name ?? 'Learner' }}
                    </p>
                    <p class="text-xs font-medium text-slate-500">
                        {{ $enrolment->course->title ?? 'Course' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="mt-6 flex md:mt-0 md:ml-4 gap-3 relative">
             <a href="{{ route('partner.installments.index') }}" 
                class="inline-flex items-center px-5 py-2.5 bg-white border border-slate-200 text-slate-600 rounded-xl text-xs font-black shadow-sm hover:bg-slate-50 hover:border-slate-300 transition-all">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Summary
            </a>
        </div>
    </div>

    @php
        $installments = $enrolment->partnerInstallments;
        $totalAmount = $installments->first()->total_amount ?? $installments->sum('installment_amount');
        $paidAmount = $installments->where('status', 'paid')->sum('paid_amount');
        $awaitingAmount = $installments->where('status', 'awaiting_approval')->sum('paid_amount');
        $pendingAmount = $totalAmount - $paidAmount;
    @endphp

    {{-- Metrics Row --}}
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden divide-x divide-slate-100 hidden md:flex items-center">
        {{-- Full Amount --}}
        <div class="flex-1 p-6 hover:bg-slate-50/50 transition-colors group">
            <div class="flex items-center gap-4">
                <div class="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-money-bill-wave text-lg"></i>
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Full Amount</p>
                    <p class="text-lg font-black text-slate-900 mt-0.5">£{{ number_format($totalAmount, 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Paid Amount --}}
        <div class="flex-1 p-6 hover:bg-slate-50/50 transition-colors group">
            <div class="flex items-center gap-4">
                <div class="h-10 w-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-circle-check text-lg"></i>
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Paid Amount</p>
                    <p class="text-lg font-black text-emerald-600 mt-0.5">£{{ number_format($paidAmount, 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Pending Amount --}}
        <div class="flex-1 p-6 hover:bg-slate-50/50 transition-colors group">
            <div class="flex items-center gap-4">
                <div class="h-10 w-10 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-clock text-lg"></i>
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Remaining</p>
                    <p class="text-lg font-black text-rose-600 mt-0.5">£{{ number_format($pendingAmount, 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Awaiting Approval --}}
        <div class="flex-1 p-6 hover:bg-slate-50/50 transition-colors group">
            <div class="flex items-center gap-4">
                <div class="h-10 w-10 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-hourglass-half text-lg"></i>
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Awaiting</p>
                    <p class="text-lg font-black text-purple-600 mt-0.5">£{{ number_format($awaitingAmount, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Metrics Grid (Fallback) --}}
    <div class="grid grid-cols-1 gap-4 md:hidden">
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-4">
             <div class="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600"><i class="fa-solid fa-money-bill-wave"></i></div>
             <div><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Full Amount</p><p class="text-base font-black text-slate-900">£{{ number_format($totalAmount, 2) }}</p></div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-4">
             <div class="h-10 w-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600"><i class="fa-solid fa-circle-check"></i></div>
             <div><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Paid Amount</p><p class="text-base font-black text-emerald-600">£{{ number_format($paidAmount, 2) }}</p></div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-4">
             <div class="h-10 w-10 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600"><i class="fa-solid fa-clock"></i></div>
             <div><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Remaining</p><p class="text-base font-black text-rose-600">£{{ number_format($pendingAmount, 2) }}</p></div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-4">
             <div class="h-10 w-10 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600"><i class="fa-solid fa-hourglass-half"></i></div>
             <div><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Awaiting</p><p class="text-base font-black text-purple-600">£{{ number_format($awaitingAmount, 2) }}</p></div>
        </div>
    </div>

    {{-- Installment Schedule Table --}}
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-black text-slate-900 tracking-tight">Installment Schedule</h3>
                <p class="text-xs text-slate-500 font-medium mt-1">Timeline of all payments and their current processing state.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-white border border-slate-200 text-slate-600">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span> Paid
                </span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-white border border-slate-200 text-slate-600">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500 mr-1.5"></span> Awaiting
                </span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-white border border-slate-200 text-slate-600">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span> Overdue
                </span>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/30">
                        <th class="px-8 py-4 font-black text-slate-400 text-[10px] uppercase tracking-widest text-center">No.</th>
                        <th class="px-8 py-4 font-black text-slate-400 text-[10px] uppercase tracking-widest">Due Date</th>
                        <th class="px-8 py-4 font-black text-slate-400 text-[10px] uppercase tracking-widest">Installment Amount</th>
                        <th class="px-8 py-4 font-black text-slate-400 text-[10px] uppercase tracking-widest">Status</th>
                        <th class="px-8 py-4 font-black text-slate-400 text-[10px] uppercase tracking-widest text-center">Proof</th>
                        <th class="px-8 py-4 font-black text-slate-400 text-[10px] uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 bg-white">
                    @foreach($installments as $inst)
                        @php
                            $today = now()->startOfDay();
                            $badgeClass = 'bg-slate-100 text-slate-600';
                            $statusText = ucfirst($inst->status);
                            $dotClass = 'bg-slate-400';

                            if ($inst->status === 'paid') {
                                $badgeClass = 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20';
                                $statusText = 'Paid';
                                $dotClass = 'bg-emerald-600';
                            } elseif ($inst->status === 'awaiting_approval') {
                                $badgeClass = 'bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-600/20';
                                $statusText = 'Awaiting Approval';
                                $dotClass = 'bg-purple-600';
                            } elseif ($inst->status === 'rejected') {
                                $badgeClass = 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20';
                                $statusText = 'Rejected';
                                $dotClass = 'bg-rose-600';
                            } else {
                                if ($inst->due_date < $today) {
                                    $badgeClass = 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20';
                                    $statusText = 'Overdue';
                                    $dotClass = 'bg-rose-600';
                                } elseif ($inst->due_date <= $today->copy()->addDays(7)) {
                                    $badgeClass = 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20';
                                    $statusText = 'Due Soon';
                                    $dotClass = 'bg-amber-600';
                                } else {
                                    $badgeClass = 'bg-slate-50 text-slate-600 ring-1 ring-inset ring-slate-600/10';
                                    $statusText = 'Pending';
                                    $dotClass = 'bg-slate-400';
                                }
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-all group">
                            <td class="px-8 py-5 text-center">
                                @if($inst->installment_no == 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-indigo-50 text-indigo-700 uppercase tracking-widest">Deposit</span>
                                @else
                                    <span class="text-xs font-black text-slate-400 group-hover:text-slate-900 transition-colors">#{{ $inst->installment_no }}</span>
                                @endif
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-slate-700">
                                        {{ $inst->due_date ? $inst->due_date->format('d M, Y') : '-' }}
                                    </span>
                                    @if($inst->status !== 'paid' && $inst->due_date < $today)
                                        <span class="text-[9px] font-black text-rose-500 uppercase tracking-widest mt-0.5">Expired</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-slate-900">£{{ number_format($inst->installment_amount, 2) }}</span>
                                    @if($inst->paid_amount > 0 && $inst->status === 'partial')
                                        <span class="text-[10px] font-bold text-emerald-600">Paid: £{{ number_format($inst->paid_amount, 2) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black tracking-tight {{ $badgeClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $dotClass }} mr-2"></span>
                                    {{ $statusText }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-center">
                                @if($inst->receipt_path)
                                    <a href="{{ asset('storage/' . $inst->receipt_path) }}" target="_blank" 
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-all shadow-sm" 
                                       title="View Proof">
                                        <i class="fa-solid fa-file-invoice text-sm"></i>
                                    </a>
                                @else
                                    <span class="text-slate-200">
                                        <i class="fa-solid fa-minus text-xs"></i>
                                    </span>
                                @endif
                            </td>
                            <td class="px-8 py-5 text-right whitespace-nowrap">
                                @if($inst->status === 'awaiting_approval')
                                    <span class="text-[10px] font-black text-purple-400 uppercase tracking-widest">Under Review</span>
                                @elseif($inst->status === 'paid')
                                     <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest"><i class="fa-solid fa-check mr-1"></i> Completed</span>
                                @else
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('partner.installments.checkout', $inst->id) }}" 
                                            class="inline-flex items-center justify-center px-4 py-2 bg-ds-pink text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-pink-700 shadow-lg shadow-pink-200 transition-all hover:-translate-y-0.5 active:translate-y-0">
                                            Pay Now
                                        </a>
                                        <button type="button" x-data @click="$dispatch('open-payment-modal', {id: {{ $inst->id }}})"
                                            class="inline-flex items-center justify-center px-4 py-2 bg-white border border-slate-200 text-slate-700 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-slate-50 shadow-sm transition-all hover:border-slate-300">
                                            Record Proof
                                        </button>
                                    </div>
                                @endif
                                
                                @if($inst->status === 'rejected' && $inst->rejection_reason)
                                    <div class="mt-1 text-[9px] font-bold text-rose-500 italic">Reason: {{ $inst->rejection_reason }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODALS CONTAINER --}}
    @foreach($installments as $inst)
        @if($inst->status != 'paid')
            <div class="modal fade fixed top-0 left-0 hidden w-full h-full outline-none overflow-x-hidden overflow-y-auto z-[9999]"
                id="payModal-{{ $inst->id }}" tabindex="-1" aria-hidden="true" 
                x-data="{}" @open-payment-modal.window="if($event.detail.id == {{ $inst->id }}) new bootstrap.Modal($el).show()">
                <div class="modal-dialog relative w-full pointer-events-none mx-auto mt-20 max-w-sm sm:max-w-md">
                    <div
                        class="modal-content border-none shadow-2xl relative flex flex-col w-full pointer-events-auto bg-white bg-clip-padding rounded-3xl outline-none text-current transform transition-all border border-slate-100 overflow-hidden">
                        <form action="{{ route('partner.installments.pay', $inst->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div
                                class="modal-header flex flex-shrink-0 items-center justify-between p-8 border-b border-gray-50 bg-slate-50/50">
                                <div>
                                    <h5 class="text-xl font-black text-slate-900 tracking-tight">Record Payment Proof</h5>
                                    <p class="text-xs text-slate-500 font-bold mt-1 uppercase tracking-widest">Manual Submission</p>
                                </div>
                                <button type="button"
                                    class="h-8 w-8 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-slate-600 transition-all flex items-center justify-center shadow-sm"
                                    data-bs-dismiss="modal" aria-label="Close">
                                    <i class="fa-solid fa-xmark text-sm"></i>
                                </button>
                            </div>
                            <div class="modal-body relative p-8 text-left space-y-6">
                                <div class="bg-indigo-50/50 p-6 rounded-2xl border border-indigo-100 relative overflow-hidden group">
                                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-16 h-16 bg-white rounded-full opacity-30 group-hover:scale-125 transition-transform duration-500"></div>
                                    <div class="relative">
                                        <div class="text-[10px] text-indigo-500 font-black uppercase tracking-widest mb-2">Selected Installment</div>
                                        <div class="font-black text-slate-900 text-base mb-1">{{ $inst->course->title ?? 'Course' }}</div>
                                        <div class="flex items-center justify-between mt-4">
                                            <div class="text-xs text-slate-600 font-bold uppercase tracking-widest">
                                                {{ $inst->installment_no == 0 ? 'Initial Deposit' : 'Installment #' . $inst->installment_no }}
                                            </div>
                                            <div class="text-xl font-black text-indigo-700">£{{ number_format($inst->installment_amount - $inst->paid_amount, 2) }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-[10px] font-black uppercase tracking-widest mb-2 text-slate-500">Amount Paid (£)</label>
                                        <input type="number" step="0.01" name="amount"
                                            class="w-full text-sm font-black border-slate-200 rounded-xl px-4 py-3 focus:ring-4 focus:ring-ds-pink/10 focus:border-ds-pink transition-all bg-slate-50/30"
                                            value="{{ $inst->installment_amount - $inst->paid_amount }}" required>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black uppercase tracking-widest mb-2 text-slate-500">Date Paid</label>
                                        <input type="date" name="payment_date"
                                            class="w-full text-sm font-bold border-slate-200 rounded-xl px-4 py-3 focus:ring-4 focus:ring-ds-pink/10 focus:border-ds-pink transition-all bg-slate-50/30 text-slate-600"
                                            value="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest mb-2 text-slate-500">Reference / Note</label>
                                    <input type="text" name="payment_reference"
                                        class="w-full text-sm font-bold border-slate-200 rounded-xl px-4 py-3 focus:ring-4 focus:ring-ds-pink/10 focus:border-ds-pink transition-all bg-slate-50/30 placeholder:text-slate-300"
                                        placeholder="e.g. Bank Ref, Cash, etc.">
                                </div>
                                <div x-data="{ fileName: null }">
                                    <label class="block text-[10px] font-black uppercase tracking-widest mb-2 text-slate-500">Receipt Image/PDF</label>
                                    <div class="mt-1 flex justify-center px-6 pt-8 pb-8 border-2 border-dashed rounded-2xl transition-all cursor-pointer group relative overflow-hidden"
                                         :class="fileName ? 'border-ds-pink bg-pink-50/30' : 'border-slate-200 hover:border-indigo-300 hover:bg-slate-50/50'">
                                        <div class="space-y-2 text-center" x-show="!fileName">
                                            <div class="h-14 w-14 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform group-hover:bg-indigo-100 group-hover:text-indigo-600 text-slate-400">
                                                <i class="fa-solid fa-cloud-arrow-up text-2xl"></i>
                                            </div>
                                            <div class="flex justify-center text-xs">
                                                <span class="font-black text-ds-pink group-hover:text-pink-700 transition-colors">Choose file</span>
                                                <p class="pl-1 text-slate-500 font-bold">or drop here</p>
                                            </div>
                                            <p class="text-[10px] text-slate-400 font-medium">High quality JPG, PNG or PDF (Max 5MB)</p>
                                        </div>
                                        <div class="space-y-2 text-center" x-show="fileName" style="display: none;">
                                            <div class="w-14 h-14 mx-auto bg-emerald-100 rounded-2xl flex items-center justify-center mb-3">
                                                <i class="fa-solid fa-file-circle-check text-emerald-600 text-2xl"></i>
                                            </div>
                                            <div class="text-xs font-black text-slate-900 truncate max-w-[250px]" x-text="fileName"></div>
                                            <div class="text-[10px] text-emerald-600 font-black uppercase tracking-widest mt-1">Ready for upload</div>
                                        </div>
                                        <input type="file" name="receipt" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                            @change="fileName = $event.target.files[0] ? $event.target.files[0].name : null">
                                    </div>
                                </div>
                            </div>
                            <div
                                class="modal-footer flex flex-shrink-0 flex-wrap items-center justify-end p-8 border-t border-gray-50 bg-slate-50/30 gap-4">
                                <button type="button"
                                    class="px-6 py-3 bg-white border border-slate-200 text-slate-500 font-black text-[10px] uppercase tracking-widest rounded-xl hover:bg-slate-50 hover:text-slate-800 transition-all"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit"
                                    class="px-8 py-3 bg-ds-navy text-white font-black text-[10px] uppercase tracking-widest rounded-xl shadow-xl shadow-slate-200 hover:bg-slate-900 hover:-translate-y-0.5 active:translate-y-0 transition-all">
                                    Submit for Review
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
