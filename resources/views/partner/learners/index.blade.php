@extends('layouts.partner')

@section('title', 'My Learners')
@section('active-page', 'learners')

@section('content')
<div class="space-y-6">
    <x-ui.card class="bg-gradient-to-r from-ds-navy to-[#0F4C81] text-white border-none overflow-hidden relative">
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-ds-pink/20 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 rounded-full bg-white/10 text-[10px] font-bold uppercase tracking-wider text-white/90 border border-white/10">
                        Partner Portal
                    </span>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold mb-2">My Learners</h1>
                <p class="text-blue-100/80 text-sm max-w-xl leading-relaxed">
                    Manage your learners, track their progress, and view their enrolment status.
                </p>
            </div>

            <x-ui.button variant="primary" href="{{ route('partner.learners.create') }}" class="shadow-lg shadow-blue-900/20 border-white/10">
                <x-slot name="icon">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </x-slot>
                Create New Learner
            </x-ui.button>
        </div>
    </x-ui.card>

    <x-ui.card class="border-slate-200 shadow-sm" padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-200">
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider">Learner Details</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider">Account Status</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider">College Approval</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider">Microsoft 365</th>
                        <th class="px-6 py-4 text-right font-bold text-slate-600 text-xs uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($pendingLearners as $learner)
                        <tr class="group bg-blue-50/30 hover:bg-blue-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 shrink-0 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm border border-blue-200 shadow-sm">
                                        {{ substr($learner->first_name, 0, 1) }}{{ substr($learner->last_name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm">{{ $learner->first_name }} {{ $learner->last_name }}</div>
                                        <div class="text-xs text-slate-500 font-medium">{{ $learner->personal_email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <x-ui.badge variant="warning" size="sm">Pending Payment</x-ui.badge>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-slate-400 font-medium">Awaiting Payment</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-slate-400 font-medium">-</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <x-ui.button variant="ghost" size="sm" href="{{ route('partner.learners.show', 'pending-' . $learner->id) }}">
                                    View Details
                                </x-ui.button>
                            </td>
                        </tr>
                    @endforeach

                    @forelse($activeLearners as $learner)
                        <tr class="group hover:bg-slate-50/80 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 shrink-0 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 font-bold text-sm border border-slate-200 shadow-sm">
                                        {{ substr($learner->first_name, 0, 1) }}{{ substr($learner->sur_name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm">{{ $learner->first_name }} {{ $learner->sur_name }}</div>
                                        <div class="text-xs text-slate-500 font-medium">{{ $learner->email_address }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($learner->status_id == 2)
                                    <x-ui.badge variant="success" size="sm">Active</x-ui.badge>
                                @elseif($learner->status_id == 1)
                                    <x-ui.badge variant="warning" size="sm">Pending</x-ui.badge>
                                @else
                                    <x-ui.badge variant="neutral" size="sm">Inactive</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($learner->crm_approved)
                                    <x-ui.badge variant="brand" size="sm">Approved</x-ui.badge>
                                @else
                                    <x-ui.badge variant="warning" size="sm">Under Review</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($learner->ms_user_id)
                                    <span class="text-xs font-semibold text-slate-700">Provisioned</span>
                                @else
                                    <span class="text-xs text-slate-400 font-medium">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <x-ui.button variant="ghost" size="sm" href="{{ route('partner.learners.show', $learner->id) }}">
                                    Manage
                                </x-ui.button>
                            </td>
                        </tr>
                    @empty
                        @if($pendingLearners->isEmpty())
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <h3 class="text-sm font-bold text-slate-900">No learners found</h3>
                                    <p class="mt-1 text-xs text-slate-500">Get started by creating a new learner profile.</p>
                                    <div class="mt-4">
                                        <x-ui.button variant="brand" href="{{ route('partner.learners.create') }}">
                                            Create Learner
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
@endsection
