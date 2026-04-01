@extends('layouts.partner')

@section('title', 'Support Tickets')
@section('active-page', 'support')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-900 leading-tight">Support Tickets</h1>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-tighter">Manage your conversations with DirectSkills staff</p>
        </div>
        <x-ui.button variant="primary" href="{{ route('partner.support.create') }}" class="bg-ds-pink border-ds-pink hover:bg-pink-700">
            <x-slot name="icon">
                <i class="fa-solid fa-plus text-xs"></i>
            </x-slot>
            New Ticket
        </x-ui.button>
    </div>

    <x-ui.card class="border-slate-200 shadow-sm overflow-hidden" padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Reference & Subject</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Status</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Last Update</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-slate-50/50 transition-colors {{ $ticket->unread_for_partner ? 'bg-blue-50/30' : '' }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 shrink-0 border border-slate-200">
                                        <i class="fa-solid fa-ticket text-sm"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-black text-ds-navy uppercase tracking-tighter">{{ $ticket->reference }}</span>
                                            @if($ticket->unread_for_partner)
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                            @endif
                                        </div>
                                        <div class="text-sm font-bold text-slate-900 truncate max-w-md">{{ $ticket->subject }}</div>
                                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-tight">{{ $ticket->category ?? 'General Support' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusClasses = match($ticket->status) {
                                        'open' => 'bg-blue-100 text-blue-700',
                                        'assigned' => 'bg-indigo-100 text-indigo-700',
                                        'waiting_support' => 'bg-amber-100 text-amber-700',
                                        'waiting_partner' => 'bg-rose-100 text-ds-pink font-bold',
                                        'resolved' => 'bg-emerald-100 text-emerald-700',
                                        'closed' => 'bg-slate-100 text-slate-600',
                                        default => 'bg-slate-100 text-slate-600'
                                    };
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter {{ $statusClasses }}">
                                    {{ str_replace('_', ' ', $ticket->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="text-xs font-bold text-slate-700">
                                    {{ $ticket->last_message_at ? $ticket->last_message_at->diffForHumans() : $ticket->created_at->diffForHumans() }}
                                </div>
                                <div class="text-[9px] text-slate-500 font-bold uppercase">
                                    By {{ $ticket->last_message_by === 'partner' ? 'You' : 'Support' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('partner.support.show', $ticket->id) }}" class="inline-flex items-center gap-1.5 text-xs font-black text-ds-navy hover:text-ds-pink transition-colors uppercase tracking-tight">
                                    View Thread
                                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <div class="max-w-xs mx-auto">
                                    <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-300 mx-auto mb-4">
                                        <i class="fa-solid fa-comment-slash text-2xl"></i>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-900">No support tickets found</h3>
                                    <p class="text-xs text-slate-500 mt-1">If you have questions or technical issues, create a new ticket to speak with our team.</p>
                                    <div class="mt-6">
                                        <x-ui.button variant="primary" href="{{ route('partner.support.create') }}" class="w-full justify-center">
                                            Start a Conversation
                                        </x-ui.button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tickets->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                {{ $tickets->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
