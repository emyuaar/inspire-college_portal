@extends('layouts.partner')
@section('title', 'Ticket Details - ' . $ticket->reference)
@section('active-page', 'support')
@section('content')
<div class="max-w-5xl mx-auto space-y-6 pb-20 mt-4" 
     x-data="supportThread()" 
     x-init="init()">
    
    {{-- Ticket Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex items-center gap-4">
            <a href="{{ route('partner.support.index') }}" class="w-10 h-10 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-100 hover:text-ds-pink transition-all shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div class="min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ $ticket->reference }}</span>
                    <span class="px-2 py-0.5 bg-ds-navy text-white text-[9px] font-black uppercase tracking-tighter rounded-full">{{ $ticket->category ?? 'Support' }}</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900 leading-tight truncate max-w-xl">{{ $ticket->subject }}</h1>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-4 py-1.5 rounded-full text-[11px] font-black uppercase tracking-widest border"
                  :class="{
                      'bg-blue-50 text-blue-700 border-blue-100': status === 'open',
                      'bg-indigo-50 text-indigo-700 border-indigo-100': status === 'assigned',
                      'bg-amber-50 text-amber-700 border-amber-100': status === 'waiting_support',
                      'bg-rose-50 text-ds-pink border-ds-pink/20': status === 'waiting_partner',
                      'bg-emerald-50 text-emerald-700 border-emerald-100': status === 'resolved',
                      'bg-slate-50 text-slate-600 border-slate-200': status === 'closed'
                  }" x-text="status.replace('_', ' ')">
            </span>
        </div>
    </div>

    {{-- Main Conversation Area --}}
    <div class="flex flex-col bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        
        {{-- Thread Messages --}}
        <div id="thread-content" 
             class="h-[550px] overflow-y-auto p-6 md:p-10 space-y-8 bg-slate-50/10 scroll-smooth relative" 
             x-ref="messagesContainer">
            
            <template x-for="message in messages" :key="message.id">
                <div class="flex flex-col" :class="message.is_self ? 'items-end' : 'items-start'">
                    <div class="max-w-[85%]">
                        <div class="px-5 py-3 rounded-2xl"
                             :class="message.is_self ? 'bg-ds-navy text-white rounded-tr-none' : 'bg-white border border-slate-200 text-slate-900 rounded-tl-none shadow-sm'">
                            <div class="flex items-center justify-between gap-8 mb-1.5">
                                <span class="text-[9px] font-black uppercase tracking-widest opacity-80"
                                      :class="message.is_self ? 'text-blue-100' : 'text-ds-pink'"
                                      x-text="message.is_self ? 'DirectSkills Partner' : 'DirectSkills Support'">
                                </span>
                                <span class="text-[8px] font-bold uppercase tracking-tighter opacity-50" x-text="message.created_at"></span>
                            </div>
                            <div class="text-[13px] leading-relaxed whitespace-pre-wrap font-medium" x-text="message.body"></div>
                        </div>
                    </div>
                </div>
            </template>

            <div x-show="messages.length === 0" class="flex flex-col items-center justify-center p-20 opacity-30 italic text-slate-500">
                <p class="text-xs">Connecting to secure encrypted channel...</p>
            </div>
        </div>

        {{-- Reply Area --}}
        <div class="shrink-0 p-6 bg-white border-t border-slate-100">
            <template x-if="status !== 'closed'">
                <form @submit.prevent="sendReply()">
                    <div class="bg-slate-50 rounded-2xl border-2 border-slate-100 focus-within:bg-white focus-within:shadow-xl transition-all overflow-hidden">
                        <textarea x-model="newMessage" rows="2" 
                                  @keydown.enter.prevent="if(!$event.shiftKey) sendReply()"
                                  class="w-full px-6 py-5 bg-transparent border-0 focus:ring-0 text-sm font-bold text-slate-900 placeholder-slate-400 min-h-[100px] resize-none" 
                                  placeholder="Reply to support... (Shift+Enter for newline)"></textarea>
                        
                        <div class="flex items-center justify-between px-6 py-4 bg-slate-50 border-t border-slate-100">
                            <div class="text-[10px] font-bold text-slate-400">
                                <i class="fa-solid fa-circle-info text-ds-pink opacity-50 mr-1"></i>
                                Average support response: 1-2 hours
                            </div>
                            <button type="submit" 
                                    class="bg-ds-pink text-white rounded-xl h-11 px-8 flex items-center justify-center gap-3 transition-all hover:bg-pink-700 hover:shadow-lg disabled:opacity-50 disabled:grayscale" 
                                    :disabled="!newMessage.trim() || sending">
                                <span x-show="!sending" class="flex items-center gap-2">
                                    <span class="text-xs font-black uppercase tracking-widest text-white">Send Reply</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                </span>
                                <span x-show="sending">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </template>
            <template x-if="status === 'closed'">
                <div class="py-10 text-center opacity-50 bg-slate-50 border border-slate-100 rounded-2xl italic font-medium">
                    <i class="fa-solid fa-lock mr-2"></i> This conversation has been resolved and closed.
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
function supportThread() {
    return {
        ticketId: '{{ $ticket->id }}',
        messages: [],
        status: '{{ $ticket->status }}',
        newMessage: '',
        sending: false,
        
        async init() {
            // Seed initial data
            this.messages = @json($mappedMessages);
            
            this.scrollToBottom();
            
            // Start Polling
            setInterval(() => this.poll(), 6000);
        },

        async poll() {
            try {
                const res = await fetch(`/partner/support/${this.ticketId}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                
                if (data.messages.length > this.messages.length) {
                    const atBottom = this.isAtBottom();
                    this.messages = data.messages;
                    this.status = data.status;
                    if (atBottom) this.$nextTick(() => this.scrollToBottom());
                } else {
                    this.status = data.status;
                }
            } catch (e) { console.error('Sync failed', e); }
        },

        async sendReply() {
            if (!this.newMessage.trim() || this.sending) return;
            
            const content = this.newMessage.trim();
            this.sending = true;
            try {
                const res = await fetch(`/partner/support/${this.ticketId}/reply`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ message: content })
                });
                const data = await res.json();
                if (data.success) {
                    this.messages.push(data.message);
                    this.newMessage = '';
                    this.scrollToBottom();
                } else { throw new Error('Send failed'); }
            } catch (e) { alert('Failed to send reply. Please try again.'); }
            finally { this.sending = false; }
        },

        scrollToBottom() {
            const el = this.$refs.messagesContainer;
            if (el) el.scrollTop = el.scrollHeight;
        },

        isAtBottom() {
            const el = this.$refs.messagesContainer;
            if (!el) return true;
            return el.scrollHeight - el.scrollTop - el.clientHeight < 150;
        }
    }
}
</script>
@endpush
@endsection
