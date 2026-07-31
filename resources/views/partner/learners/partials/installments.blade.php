@if($installments->isNotEmpty())
    {{-- Summary Calculation --}}
    @php
        $totalInstallments = $installments->count();
        $paidInstallments = $installments->where('status', 'paid')->count();
        $pendingInstallments = $installments->where('status', '!=', 'paid')->where('due_date', '>=', now())->count();
        $overdueInstallments = $installments->where('status', '!=', 'paid')->where('due_date', '<', now())->count();

        $totalAmount = $installments->sum('installment_amount');
        $paidAmount = $installments->sum('paid_amount');
        $remainingAmount = $totalAmount - $paidAmount;

        // Get unique courses for tabs
        $courses = $installments->map(fn($i) => $i->course)->filter()->unique('id')->values();
    @endphp

    {{-- Alpine Logic (Script Moved Top) --}}
    <script>
        function installmentTable() {
            return {
                activeTab: 'all', // 'all' or course_id
                filter: 'all', // all, pending, paid, overdue
                search: '',
                sortBy: 'date_asc', // date_asc, date_desc, status, amount_desc
                currentPage: 1,
                pageSize: 10,
                rows: [
                    @foreach($installments as $inst)
                                        {
                            id: {{ $inst->id }},
                            course_id: {{ $inst->course_id ?? 'null' }},
                            due_date: {!! json_encode(optional($inst->due_date)->format('c')) !!}, // ISO 8601 full
                            due_date_formatted: {!! json_encode(optional($inst->due_date)->format("M d, Y") ?? 'N/A') !!},
                            urgency: {!! json_encode($inst->urgency) !!},
                            is_overdue: {!! json_encode($inst->urgency === 'overdue') !!},
                            is_due_soon: {!! json_encode($inst->urgency === 'due_soon') !!},
                            label: {!! json_encode($inst->installment_no == 0 ? "Deposit" : "Month " . $inst->installment_no) !!},
                            course_title: {!! json_encode($inst->course->title ?? "Course") !!},
                            amount: {!! json_encode((float) ($inst->installment_amount ?? 0)) !!},
                            paid: {!! json_encode((float) ($inst->paid_amount ?? 0)) !!},
                            status: {!! json_encode(strtolower($inst->status ?? 'pending')) !!},
                            receipt_path: {!! json_encode($inst->receipt_path) !!},
                            rejection_reason: {!! json_encode($inst->rejection_reason) !!},
                            checkout_url: {!! json_encode(route("partner.installments.checkout", $inst->id)) !!}
                        },
                    @endforeach
                            ],
                init() {
                    // Reset pagination on filter changes
                    this.$watch('activeTab', () => this.currentPage = 1);
                    this.$watch('filter', () => this.currentPage = 1);
                    this.$watch('search', () => this.currentPage = 1);
                    this.$watch('sortBy', () => this.currentPage = 1); // Optional, but good UX
                },
                get sortedRows() {
                    // FIX: Sort a copy, do not mutate rows in-place
                    let result = [...this.rows];

                    // 1. Course Tab Filter
                    if (this.activeTab !== 'all') {
                        result = result.filter(row => row.course_id == this.activeTab);
                    }

                    // 2. Search
                    if (this.search) {
                        const lowerSearch = this.search.toLowerCase();
                        result = result.filter(row =>
                            (row.course_title || '').toLowerCase().includes(lowerSearch) ||
                            (row.label || '').toLowerCase().includes(lowerSearch) ||
                            (row.status || '').toLowerCase().includes(lowerSearch) ||
                            (row.due_date_formatted || '').toLowerCase().includes(lowerSearch)
                        );
                    }

                    // 3. Status Filter
                    if (this.filter === 'pending') {
                        result = result.filter(row => row.status !== 'paid');
                    } else if (this.filter === 'paid') {
                        result = result.filter(row => row.status === 'paid');
                    } else if (this.filter === 'overdue') {
                        // Only show rows that are actually overdue
                        result = result.filter(row => row.is_overdue);
                    }

                    // 4. Sort
                    result.sort((a, b) => {
                        if (this.sortBy === 'date_asc') return new Date(a.due_date || 0) - new Date(b.due_date || 0);
                        if (this.sortBy === 'date_desc') return new Date(b.due_date || 0) - new Date(a.due_date || 0);
                        if (this.sortBy === 'amount_desc') return b.amount - a.amount;
                        if (this.sortBy === 'status') {
                            // Pending > Overdue? actually user wants: Pending First
                            // Let's simplify: Overdue (=0), Pending (=1), Paid (=2)
                            const score = (row) => {
                                if (row.is_overdue) return 0;
                                if (row.status !== 'paid') return 1;
                                return 2;
                            };
                            return score(a) - score(b);
                        }
                        return 0;
                    });

                    return result;
                },
                get paginatedRows() {
                    const start = (this.currentPage - 1) * this.pageSize;
                    return this.sortedRows.slice(start, start + this.pageSize);
                },
                get totalPages() {
                    return Math.max(1, Math.ceil(this.sortedRows.length / this.pageSize));
                },
                get fromItem() {
                    if (this.sortedRows.length === 0) return 0;
                    return (this.currentPage - 1) * this.pageSize + 1;
                },
                get toItem() {
                    return Math.min(this.currentPage * this.pageSize, this.sortedRows.length);
                },
                formatDate(isoDate) {
                    if (!isoDate) return 'N/A';
                    const date = new Date(isoDate);
                    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' });
                },
                getStatusBadgeClass(status) {
                    if (!status) return 'bg-slate-100 text-slate-600 border border-slate-200';
                    switch (status) {
                        case 'paid': return 'bg-green-100 text-green-800 border border-green-200';
                        case 'partial': return 'bg-amber-100 text-amber-800 border border-amber-200';
                        case 'awaiting_approval': return 'bg-blue-100 text-blue-800 border border-blue-200';
                        case 'rejected': return 'bg-red-100 text-red-800 border border-red-200';
                        // Note: 'overdue' is computed, not a database status usually, but if needed:
                        case 'overdue': return 'bg-red-100 text-red-800 border border-red-200';
                        default: return 'bg-slate-100 text-slate-600 border border-slate-200';
                    }
                }
            }
        }
    </script>

    <div class="mt-8" x-data="installmentTable()" id="financial-section">
        <div class="mb-4 flex flex-col md:flex-row gap-4 items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800">Installment Plan</h3>

            {{-- Summary Badges --}}
            <div class="flex gap-2 text-xs">
                <div class="px-3 py-1.5 rounded-full bg-white border border-slate-200 shadow-sm text-slate-600">
                    <span class="font-bold text-slate-900">{{ $paidInstallments }}/{{ $totalInstallments }}</span> Paid
                </div>
                @if($overdueInstallments > 0)
                    <div class="px-3 py-1.5 rounded-full bg-red-50 border border-red-100 shadow-sm text-red-600">
                        <span class="font-bold">{{ $overdueInstallments }}</span> Overdue
                    </div>
                @endif
                <div class="px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 shadow-sm text-blue-700">
                    Remaining: <span class="font-bold">£{{ number_format($remainingAmount, 2) }}</span>
                </div>
            </div>
        </div>

        <x-ui.card class="overflow-hidden">

            {{-- Course Tabs --}}
            <div class="flex items-center gap-1 p-2 bg-slate-50 border-b border-slate-100 overflow-x-auto">
                <button @click="activeTab = 'all'"
                    class="px-4 py-2 text-xs font-bold rounded-md transition-all whitespace-nowrap"
                    :class="activeTab === 'all' ? 'bg-white text-blue-600 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:bg-slate-200/50 hover:text-slate-700'">
                    All Courses
                </button>
                @foreach($courses as $course)
                    <button @click="activeTab = {{ $course->id }}"
                        class="px-4 py-2 text-xs font-bold rounded-md transition-all whitespace-nowrap max-w-[200px] truncate"
                        :class="activeTab === {{ $course->id }} ? 'bg-white text-blue-600 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:bg-slate-200/50 hover:text-slate-700'"
                        title="{{ $course->title }}">
                        {{ $course->title }}
                    </button>
                @endforeach
            </div>

            {{-- Controls Toolbar --}}
            <div
                class="p-4 border-b border-slate-100 bg-white flex flex-col md:flex-row gap-4 justify-between items-center">

                {{-- Left: Filter Buttons --}}
                <div class="flex items-center gap-1 w-full md:w-auto overflow-x-auto pb-1 md:pb-0">
                    <button @click="filter = 'all'"
                        :class="filter === 'all' ? 'bg-slate-800 text-white shadow-md' : 'bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100'"
                        class="px-3 py-1.5 rounded-md text-xs font-bold transition-all whitespace-nowrap">
                        All Status
                    </button>
                    <button @click="filter = 'pending'"
                        :class="filter === 'pending' ? 'bg-amber-500 text-white shadow-md' : 'bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100'"
                        class="px-3 py-1.5 rounded-md text-xs font-bold transition-all whitespace-nowrap">
                        Pending
                    </button>
                    <button @click="filter = 'paid'"
                        :class="filter === 'paid' ? 'bg-emerald-500 text-white shadow-md' : 'bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100'"
                        class="px-3 py-1.5 rounded-md text-xs font-bold transition-all whitespace-nowrap">
                        Paid
                    </button>
                    <button @click="filter = 'overdue'"
                        :class="filter === 'overdue' ? 'bg-red-500 text-white shadow-md' : 'bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100'"
                        class="px-3 py-1.5 rounded-md text-xs font-bold transition-all whitespace-nowrap">
                        Overdue
                    </button>
                </div>

                {{-- Right: Search & Sort --}}
                <div class="flex items-center gap-2 w-full md:w-auto">
                    {{-- Sort --}}
                    <select x-model="sortBy"
                        class="px-3 py-1.5 text-xs border-slate-200 rounded-md focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50 shadow-sm text-slate-600 font-medium">
                        <option value="date_asc">Due Date (Soonest)</option>
                        <option value="date_desc">Due Date (Latest)</option>
                        <option value="status">Status (Pending First)</option>
                        <option value="amount_desc">Amount (High to Low)</option>
                    </select>

                    {{-- Search --}}
                    <div class="relative flex-1 md:flex-none">
                        <input x-model="search" type="text" placeholder="Search..."
                            class="pl-8 pr-3 py-1.5 text-xs w-full md:w-48 border-slate-200 rounded-md focus:ring-blue-500/20 focus:border-blue-500 bg-slate-50 shadow-sm placeholder:text-slate-400">
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto relative min-h-[200px]">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-3 w-32 whitespace-nowrap">Due Date</th>
                            <th class="px-4 py-3 min-w-[200px]">Installment</th>
                            <th class="px-4 py-3 w-28 text-right">Amount</th>
                            <th class="px-4 py-3 w-28 text-right">Paid</th>
                            <th class="px-4 py-3 w-28 text-center">Status</th>
                            <th class="px-4 py-3 w-40 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="row in paginatedRows" :key="row.id">
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap align-top">
                                    <div class="font-medium text-slate-700" x-text="formatDate(row.due_date)"></div>
                                    <div x-show="row.is_overdue"
                                        class="text-[10px] text-red-500 font-bold uppercase tracking-wider mt-0.5">Overdue
                                    </div>
                                    <div x-show="row.is_due_soon"
                                        class="text-[10px] text-amber-500 font-bold uppercase tracking-wider mt-0.5">Due Soon
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex flex-col">
                                        <span class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-0.5"
                                            x-text="row.label"></span>
                                        <span class="text-slate-700 font-medium leading-snug line-clamp-2"
                                            :title="row.course_title" x-text="row.course_title"></span>
                                        <template x-if="row.status === 'rejected' && row.rejection_reason">
                                            <div class="mt-1 flex items-start gap-1.5 p-1.5 bg-red-50 border border-red-100 rounded text-[10px] text-red-600 italic">
                                                <svg class="w-3 h-3 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span x-text="'Rejected: ' + row.rejection_reason"></span>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-slate-700 align-top"
                                    x-text="'£' + Number(row.amount).toFixed(2)"></td>
                                <td class="px-4 py-3 text-right text-slate-500 align-top"
                                    x-text="'£' + Number(row.paid).toFixed(2)"></td>
                                <td class="px-4 py-3 text-center align-top">
                                    <span :class="getStatusBadgeClass(row.status)"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold capitalize shadow-sm"
                                        x-text="row.status"></span>
                                </td>
                                <td class="px-4 py-3 text-right align-top">
                                    <div x-show="row.status !== 'paid' && row.status !== 'awaiting_approval'" class="flex gap-2 justify-end">
                                        {{-- Pay Button --}}
                                        <a :href="row.checkout_url"
                                            class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-ds-navy rounded shadow-sm hover:bg-[#0B1220] hover:shadow-md transition-all active:scale-95 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ds-navy">
                                            Pay
                                        </a>

                                        {{-- Proof Button --}}
                                        <button type="button" data-bs-toggle="modal" :data-bs-target="'#payModal-' + row.id"
                                            class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium leading-none text-slate-700 bg-white border border-slate-200 rounded shadow-sm hover:border-ds-navy hover:text-ds-navy hover:bg-slate-50 transition-all active:scale-95 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ds-navy">
                                            Proof
                                        </button>
                                    </div>

                                    {{-- Awaiting Approval State --}}
                                    <div x-show="row.status === 'awaiting_approval'" class="flex flex-col items-end gap-1">
                                         <span class="text-[10px] font-bold text-blue-600 uppercase tracking-tight bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100">Awaiting Approval</span>
                                         <template x-if="row.receipt_path">
                                             <a :href="'/storage/' + row.receipt_path" target="_blank" class="text-[10px] text-blue-500 underline hover:text-blue-700 font-medium transition-colors">View Submitted Proof</a>
                                         </template>
                                    </div>

                                    <div x-show="row.status === 'paid'"
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Paid
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="paginatedRows.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400 italic">No installments found
                                matching your criteria.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Pagination Footer --}}
            <div
                class="p-4 border-t border-slate-100 bg-slate-50 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-medium text-slate-600">
                <div>
                    Showing <span x-text="fromItem"></span> to <span x-text="toItem"></span> of <span
                        x-text="sortedRows.length"></span> entries
                </div>

                <div class="flex items-center gap-2">
                    <button @click="currentPage--" :disabled="currentPage === 1"
                        class="px-3 py-1.5 rounded-md border border-slate-200 bg-white hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-bold">
                        Previous
                    </button>

                    {{-- Page Indicator --}}
                    <span class="px-2">Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span></span>

                    <button @click="currentPage++" :disabled="currentPage >= totalPages"
                        class="px-3 py-1.5 rounded-md border border-slate-200 bg-white hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-bold">
                        Next
                    </button>
                </div>
            </div>

        </x-ui.card>

        {{-- MODALS CONTAINER (Moved outside table) --}}
        @foreach($installments as $inst)
            @if($inst->status != 'paid')
                <div class="modal fade fixed top-0 left-0 hidden w-full h-full outline-none overflow-x-hidden overflow-y-auto z-[60]"
                    id="payModal-{{ $inst->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="false">
                    <div class="modal-dialog relative w-full pointer-events-none mx-auto mt-20 max-w-sm sm:max-w-md">
                        <div
                            class="modal-content border-none shadow-xl relative flex flex-col w-full pointer-events-auto bg-white bg-clip-padding rounded-lg outline-none text-current transform transition-all">
                            <form action="{{ route('partner.installments.pay', $inst->id) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div
                                    class="modal-header flex flex-shrink-0 items-center justify-between p-5 border-b border-gray-100 rounded-t-lg bg-slate-50">
                                    <div>
                                        <h5 class="text-base font-bold text-slate-800">Record Payment</h5>
                                        <p class="text-xs text-slate-500 mt-0.5">Upload proof or record manual transfer</p>
                                    </div>
                                    <button type="button"
                                        class="btn-close box-content w-4 h-4 p-1 text-slate-400 border-none rounded-none opacity-50 hover:opacity-100 focus:outline-none"
                                        data-bs-dismiss="modal" aria-label="Close">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="modal-body relative p-6 text-left space-y-5">
                                    @if($inst->status === 'rejected')
                                        <div class="p-3 bg-red-50 rounded border border-red-100 text-xs text-red-700">
                                            <span class="font-bold block mb-1">Previous Proof Rejected:</span>
                                            {{ $inst->rejection_reason ?? 'Please upload a valid payment receipt.' }}
                                        </div>
                                    @endif

                                    <div class="bg-blue-50 p-3 rounded border border-blue-100">
                                        <div class="text-xs text-blue-500 font-semibold uppercase tracking-wide mb-1">Paying for
                                        </div>
                                        <div class="font-bold text-blue-900 text-sm">{{ $inst->course->title ?? 'Course' }}</div>
                                        <div class="text-xs text-blue-700 mt-0.5">
                                            {{ $inst->installment_no == 0 ? 'Deposit' : 'Month ' . $inst->installment_no }}
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold mb-1.5 text-slate-600">Amount (£)</label>
                                            <input type="number" step="0.01" name="amount"
                                                class="w-full text-sm border-slate-200 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium"
                                                value="{{ $inst->installment_amount - $inst->paid_amount }}" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold mb-1.5 text-slate-600">Date Paid</label>
                                            <input type="date" name="payment_date"
                                                class="w-full text-sm border-slate-200 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all text-slate-600"
                                                value="{{ date('Y-m-d') }}" required>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold mb-1.5 text-slate-600">Payment Reference <span
                                                class="text-slate-400 font-normal">(Optional)</span></label>
                                        <input type="text" name="payment_reference"
                                            class="w-full text-sm border-slate-200 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all placeholder:text-slate-300"
                                            placeholder="e.g. Bank Transfer Ref, Check #123">
                                    </div>
                                    <div>
                                         <label class="block text-xs font-bold mb-1.5 text-slate-600">Proof of Payment</label>
                                         <input type="file" name="receipt" required
                                             class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-600 hover:file:bg-slate-200 cursor-pointer">
                                     </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1.5 text-slate-600">Additional Notes <span
                                                class="text-slate-400 font-normal">(Optional)</span></label>
                                        <textarea name="notes" rows="2"
                                            class="w-full text-sm border-slate-200 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all placeholder:text-slate-300"
                                            placeholder="Any details for admissions..."></textarea>
                                    </div>
                                </div>
                                <div
                                    class="modal-footer flex flex-shrink-0 flex-wrap items-center justify-end p-4 border-t border-gray-100 rounded-b-lg gap-3 bg-slate-50">
                                    <button type="button"
                                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 font-bold text-xs rounded-md shadow-sm hover:bg-slate-50 hover:text-slate-800 transition-all"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit"
                                        class="px-5 py-2 bg-blue-600 text-white font-bold text-xs rounded-md shadow-md hover:bg-blue-700 hover:shadow-lg transition-all transform active:scale-95">Record
                                        Payment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach

    </div>

    {{-- Custom Modal Overlay (Backdrop Replacement) --}}
    <div id="custom-modal-overlay"></div>

    <style>
        #custom-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            z-index: 59;
            /* Below modal (z-[60]), above page */
            display: none;
        }
    </style>

    <script>
        document.addEventListener('shown.bs.modal', function (event) {
            // Only toggle for our specific modals if needed, or globally for now as requested
            document.getElementById('custom-modal-overlay').style.display = 'block';
        });

        document.addEventListener('hidden.bs.modal', function (event) {
            // Check if any other modals are still open (Bootstrap handles multiple modals poorly by default, but assumption is single modal here)
            // Simple check: if no .modal.show exists
            if (!document.querySelector('.modal.show')) {
                document.getElementById('custom-modal-overlay').style.display = 'none';
            }
        });
    </script>
@endif