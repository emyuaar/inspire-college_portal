@extends('layouts.partner')

@section('title', 'Dashboard')
@section('active-page', 'dashboard')

@section('content')
<div class="space-y-6">

    {{-- 1. WELCOME HERO --}}
    <x-ui.card class="bg-gradient-to-r from-ds-navy to-[#0F4C81] text-white border-none overflow-hidden relative">
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-48 h-48 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-1/4 -mb-10 w-32 h-32 bg-ds-pink/20 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-2">
                    Welcome back, {{ $partner->first_name }}!
                </h1>
                <p class="text-blue-100/80 text-sm max-w-xl leading-relaxed">
                    Here's a quick overview of your partnership performance. You have <span class="text-white font-bold">{{ $pendingApprovals }}</span> learners awaiting CRM approval.
                </p>
                <div class="flex flex-wrap gap-3 mt-4">
                    <div class="flex items-center gap-1.5 px-3 py-1 bg-white/10 border border-white/10 rounded-full text-[11px] font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Platform Online
                    </div>
                    <div class="flex items-center gap-1.5 px-3 py-1 bg-white/10 border border-white/10 rounded-full text-[11px] font-medium">
                        <i class="fa-solid fa-calendar-days text-blue-300"></i>
                        {{ now()->format('D, d M Y') }}
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                <x-ui.button variant="primary" href="{{ route('partner.learners.create') }}" class="shadow-lg shadow-blue-900/40 border-white/10 bg-ds-pink border-ds-pink hover:bg-pink-700">
                    <x-slot name="icon">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </x-slot>
                    Onboard New Learner
                </x-ui.button>
                <x-ui.button variant="ghost" href="{{ route('partner.courses.index') }}" class="text-white border-white/20 hover:bg-white/10 no-underline">
                   Browse Courses
                </x-ui.button>
            </div>
        </div>
    </x-ui.card>

    {{-- 2. METRICS GRID --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
        {{-- Total Learners --}}
        <x-ui.card class="h-full border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Total Learners</p>
                    <h3 class="text-xl font-black text-slate-900">{{ number_format($totalLearners) }}</h3>
                    <p class="text-[9px] text-emerald-600 font-bold mt-1 flex items-center gap-1">
                       <i class="fas fa-arrow-up"></i> Growing
                    </p>
                </div>
                <div class="p-2 bg-blue-50 rounded-lg text-ds-navy">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
        </x-ui.card>

        {{-- Pending Plans (DECISION Metric) --}}
        @if($pendingPlansCount > 0)
        <x-ui.card class="h-full border-ds-pink/20 shadow-sm relative overflow-hidden group bg-ds-pink/5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-bold text-ds-pink uppercase tracking-wider mb-1">Pending Plans</p>
                    <h3 class="text-xl font-black text-slate-900">{{ number_format($pendingPlansCount) }}</h3>
                    <a href="{{ route('partner.enrolments.pending_plans') }}" class="text-[9px] text-ds-pink font-bold mt-1 hover:underline flex items-center gap-1">
                       Review Decisions <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <div class="p-2 bg-ds-pink text-white rounded-lg shadow-sm">
                    <i class="fa-solid fa-file-invoice text-sm"></i>
                </div>
            </div>
        </x-ui.card>
        @endif

        {{-- Active Accounts --}}
        <x-ui.card class="h-full border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Active Accounts</p>
                    <h3 class="text-xl font-black text-slate-900">{{ number_format($activeLearners) }}</h3>
                    <p class="text-[9px] text-slate-400 font-medium mt-1 uppercase tracking-tighter">Live on M365</p>
                </div>
                <div class="p-2 bg-emerald-50 rounded-lg text-emerald-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </x-ui.card>

        {{-- Total Revenue --}}
        <x-ui.card class="h-full border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Received Fees</p>
                    <h3 class="text-xl font-black text-slate-900">£{{ number_format($totalRevenue, 0) }}</h3>
                    <p class="text-[9px] text-slate-400 font-medium mt-1 uppercase tracking-tighter">Total Collected</p>
                </div>
                <div class="p-2 bg-pink-50 rounded-lg text-ds-pink">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </x-ui.card>

        {{-- Assigned Courses --}}
        <x-ui.card class="h-full border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Partner Courses</p>
                    <h3 class="text-xl font-black text-slate-900">{{ number_format($assignedCoursesCount) }}</h3>
                    <p class="text-[9px] text-blue-600 font-bold mt-1 uppercase tracking-tighter">Active Pricing</p>
                </div>
                <div class="p-2 bg-amber-50 rounded-lg text-amber-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- 3. ALERTS SECTION (Conditional) --}}
    @php 
        $hasAlerts = $pendingApprovals > 0 || $overdueInstallmentsCount > 0 || $dueSoonInstallmentsCount > 0 || $pendingPlansCount > 0;
    @endphp

    @if($hasAlerts)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @if($pendingApprovals > 0)
        <div class="flex items-center gap-4 p-4 bg-amber-50 border border-amber-100 rounded-2xl">
            <div class="flex-shrink-0 w-10 h-10 bg-amber-100 text-amber-700 rounded-full flex items-center justify-center">
                <i class="fa-solid fa-user-clock text-base"></i>
            </div>
            <div class="flex-1">
                <h4 class="text-xs font-bold text-amber-900 leading-tight uppercase tracking-tight">CRM Review</h4>
                <p class="text-[11px] text-amber-700 mt-0.5">{{ $pendingApprovals }} learners awaiting admin approval.</p>
            </div>
            <a href="{{ route('partner.learners.index') }}" class="text-[11px] font-bold text-amber-800 hover:underline">View All</a>
        </div>
        @endif

        @if($pendingPlansCount > 0)
        <div class="flex items-center gap-4 p-4 bg-rose-50 border border-rose-100 rounded-2xl shadow-sm border-l-4 border-l-ds-pink">
            <div class="flex-shrink-0 w-10 h-10 bg-rose-100 text-ds-pink rounded-full flex items-center justify-center">
                <i class="fa-solid fa-file-invoice text-base"></i>
            </div>
            <div class="flex-1">
                <h4 class="text-xs font-bold text-rose-900 leading-tight uppercase tracking-tight">Plan Review Required</h4>
                <p class="text-[11px] text-rose-700 mt-0.5">{{ $pendingPlansCount }} enrolments need plan selection.</p>
            </div>
            <a href="{{ route('partner.enrolments.pending_plans') }}" class="text-[11px] font-bold text-rose-800 hover:underline">Review Plans</a>
        </div>
        @endif

        @if($overdueInstallmentsCount > 0)
        <a href="{{ route('partner.installments.index', ['status' => 'overdue']) }}" class="flex items-center gap-4 p-4 bg-rose-50 border border-rose-100 rounded-2xl hover:bg-rose-100 transition-colors group border-l-4 border-l-rose-500">
            <div class="flex-shrink-0 w-10 h-10 bg-rose-100 text-rose-700 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-triangle-exclamation text-base"></i>
            </div>
            <div class="flex-1">
                <h4 class="text-xs font-bold text-rose-900 leading-tight uppercase tracking-tight">Overdue Payments</h4>
                <p class="text-[11px] text-rose-700 mt-0.5">{{ $overdueInstallmentsCount }} payments are past their due date.</p>
            </div>
            <span class="text-[11px] font-bold text-rose-800 hover:underline">Settle Now</span>
        </a>
        @endif

        @if($dueSoonInstallmentsCount > 0)
        <a href="{{ route('partner.installments.index', ['status' => 'due_soon']) }}" class="flex items-center gap-4 p-4 bg-blue-50 border border-blue-100 rounded-2xl hover:bg-blue-100 transition-colors group">
            <div class="flex-shrink-0 w-10 h-10 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-credit-card text-base"></i>
            </div>
            <div class="flex-1">
                <h4 class="text-xs font-bold text-blue-900 leading-tight uppercase tracking-tight">Payments Due Soon</h4>
                <p class="text-[11px] text-blue-700 mt-0.5">{{ $dueSoonInstallmentsCount }} payments due within 7 days.</p>
            </div>
            <span class="text-[11px] font-bold text-blue-800 hover:underline">Manage</span>
        </a>
        @endif
    </div>
    @endif

    {{-- 4. TABLES SECTION --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        {{-- Recent Learners --}}
        <x-ui.card class="border-slate-200 shadow-sm" padding="p-0">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Recent Onboardings</h3>
                    <p class="text-[10px] text-slate-500 font-medium uppercase tracking-tight">Newly added learner accounts</p>
                </div>
                <a href="{{ route('partner.learners.index') }}" class="text-xs font-bold text-ds-pink hover:text-pink-700 transition-colors">Manage All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-200/60">
                            <th class="px-4 py-3 font-bold text-slate-600 text-[10px] uppercase">Learner</th>
                            <th class="px-4 py-3 font-bold text-slate-600 text-[10px] uppercase">Enrolment</th>
                            <th class="px-4 py-3 font-bold text-slate-600 text-[10px] uppercase text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentLearners as $learner)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-600 border border-slate-200 shrink-0">
                                            {{ substr($learner->first_name, 0, 1) }}{{ substr($learner->sur_name, 0, 1) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-slate-900 truncate">{{ $learner->first_name }} {{ $learner->sur_name }}</div>
                                            <div class="text-[9px] text-slate-500 font-medium truncate">{{ $learner->email_address }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-xs font-medium text-slate-700 truncate max-w-[120px]">
                                        {{ $learner->enrolments->first()->course->title ?? 'No Enrolment' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($learner->status_id == 2)
                                        <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[9px] font-bold uppercase">Active</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[9px] font-bold uppercase">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-slate-400 text-xs italic">No learners found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        {{-- Financial Activity --}}
        <x-ui.card class="border-slate-200 shadow-sm" padding="p-0">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Recent Transactions</h3>
                    <p class="text-[10px] text-slate-500 font-medium uppercase tracking-tight">Payments received for your learners</p>
                </div>
                <a href="{{ route('partner.transactions.index') }}" class="text-xs font-bold text-ds-pink hover:text-pink-700 transition-colors">View Reports</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-200/60">
                            <th class="px-4 py-3 font-bold text-slate-600 text-[10px] uppercase">Learner</th>
                            <th class="px-4 py-3 font-bold text-slate-600 text-[10px] uppercase">Date</th>
                            <th class="px-4 py-3 font-bold text-slate-600 text-[10px] uppercase text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentTransactions as $tx)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="text-xs font-bold text-slate-900 truncate">{{ $tx->learner->first_name }} {{ $tx->learner->sur_name }}</div>
                                    <div class="text-[9px] text-slate-500 font-medium truncate">{{ $tx->course_title }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500 italic">
                                    {{ $tx->date->format('d M, H:i') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="text-xs font-black text-slate-900">£{{ number_format($tx->amount, 2) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-12 text-center text-slate-400 text-xs italic">No transactions recorded</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

    </div>

    {{-- 5. SUPPORT & QUICK LINKS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <x-ui.card class="border-slate-200 shadow-sm transition-all hover:shadow-md">
             <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-ds-navy/10 text-ds-navy rounded-xl flex items-center justify-center border border-ds-navy/20">
                    <i class="fa-solid fa-book-open text-xl"></i>
                </div>
                <div>
                   <h4 class="text-base font-bold text-slate-900 leading-none">Partner Guide</h4>
                   <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Instructions</span>
                </div>
            </div>
            <p class="text-sm text-slate-600 leading-relaxed mb-6 h-12 overflow-hidden">Everything you need to know about managing learners and your partner account.</p>
            <x-ui.button variant="ghost" class="w-full text-xs font-bold justify-center border border-slate-200 hover:bg-slate-50">
                Read Instructions
            </x-ui.button>
        </x-ui.card>

        <x-ui.card class="border-slate-200 shadow-sm transition-all hover:shadow-md">
             <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center border border-blue-100">
                    <i class="fa-solid fa-headset text-xl"></i>
                </div>
                <div>
                   <h4 class="text-base font-bold text-slate-900 leading-none">Support Desk</h4>
                   <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Help center</span>
                </div>
            </div>
            <p class="text-sm text-slate-600 leading-relaxed mb-6 h-12 overflow-hidden">Facing technical issues? Our admission and support team is ready to help.</p>
            <x-ui.button variant="ghost" class="w-full text-xs font-bold justify-center border border-slate-200 hover:bg-slate-50">
                Contact Team
            </x-ui.button>
        </x-ui.card>

        <x-ui.card class="border-ds-pink/20 shadow-sm transition-all hover:shadow-md relative overflow-hidden">
            {{-- Decorative Background --}}
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-ds-pink/5 rounded-full"></div>
            
            <div class="flex items-center gap-4 mb-4 relative z-10">
                <div class="w-12 h-12 bg-ds-pink text-white rounded-xl flex items-center justify-center shadow-lg shadow-ds-pink/20">
                    <i class="fa-solid fa-bolt text-xl"></i>
                </div>
                <div>
                   <h4 class="text-base font-bold text-slate-900 leading-none">Expand Portfolio</h4>
                   <span class="text-[10px] text-ds-pink font-bold uppercase tracking-wider">Request New</span>
                </div>
            </div>
            <p class="text-sm text-slate-600 leading-relaxed mb-6 h-12 overflow-hidden">Request access to new courses or bespoke pricing models for your organization.</p>
            <x-ui.button variant="secondary" class="w-full text-xs font-bold justify-center">
                Upgrade Portfolio
            </x-ui.button>
        </x-ui.card>
    </div>

</div>
@endsection
