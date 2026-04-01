<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Crm\SupportTicket;
use App\Models\Crm\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    private function notifyCrmStaff($ticket, $message)
    {
        try {
            // Find all CRM users with support/partner view permissions or just all admins
            $staffIds = DB::connection('mysql_crm')->table('users')->pluck('id');
            
            foreach ($staffIds as $userId) {
                DB::connection('mysql_crm')->table('notifications')->insert([
                    'id' => Str::uuid(),
                    'type' => 'App\Notifications\SupportTicketReceived',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => $userId,
                    'data' => json_encode([
                        'ticket_id' => $ticket->id,
                        'reference' => $ticket->reference,
                        'subject' => $ticket->subject,
                        'partner_name' => Auth::user()->first_name . ' ' . Auth::user()->sur_name,
                        'message_snippet' => Str::limit($message->body, 100),
                        'action_url' => "/admin/support/{$ticket->id}"
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to notify CRM staff: ' . $e->getMessage());
        }
    }
    public function index()
    {
        $partnerId = Auth::id();
        $tickets = SupportTicket::where('partner_id', $partnerId)
            ->orderBy('last_message_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('partner.support.index', compact('tickets'));
    }

    public function create()
    {
        return view('partner.support.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'nullable|string|max:50',
            'message' => 'required|string|max:5000',
        ]);

        $partnerId = Auth::id();
        $reference = 'TKT-' . strtoupper(Str::random(8));

        $ticket = SupportTicket::create([
            'reference' => $reference,
            'partner_id' => $partnerId,
            'subject' => $request->subject,
            'category' => $request->category,
            'status' => 'open',
            'last_message_at' => now(),
            'last_message_by' => 'partner',
            'unread_for_crm' => true,
            'unread_for_partner' => false,
        ]);

        $message = SupportMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'partner',
            'sender_id' => $partnerId,
            'body' => $request->message,
            'is_internal_note' => false,
        ]);

        $this->notifyCrmStaff($ticket, $message);

        return redirect()->route('partner.support.show', $ticket->id)
            ->with('success', 'Support ticket created successfully.');
    }

    public function show(SupportTicket $ticket)
    {
        if ($ticket->partner_id !== Auth::id()) {
            abort(403);
        }

        // Mark as read for partner
        $ticket->update(['unread_for_partner' => false]);

        $messages = $ticket->messages()
            ->where('is_internal_note', false)
            ->orderBy('created_at', 'asc')
            ->get();

        $mappedMessages = $messages->map(function($msg) {
            return [
                'id' => $msg->id,
                'body' => $msg->body,
                'sender_type' => $msg->sender_type,
                'created_at' => $msg->created_at->format('d M, H:i'),
                'is_self' => $msg->sender_type === 'partner'
            ];
        });

        if (request()->wantsJson()) {
            return response()->json([
                'status' => $ticket->status,
                'unread' => $ticket->unread_for_partner,
                'messages' => $mappedMessages
            ]);
        }

        return view('partner.support.show', compact('ticket', 'messages', 'mappedMessages'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        if ($ticket->partner_id !== Auth::id()) {
            abort(403);
        }

        if ($ticket->status === 'closed') {
            if ($request->wantsJson()) return response()->json(['error' => 'Ticket is closed'], 422);
            return back()->with('error', 'This ticket is closed and cannot be replied to.');
        }

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $partnerId = Auth::id();

        $message = SupportMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'partner',
            'sender_id' => $partnerId,
            'body' => $request->message,
            'is_internal_note' => false,
        ]);

        $this->notifyCrmStaff($ticket, $message);

        $status = $ticket->status;
        if ($status === 'waiting_partner') {
            $status = 'assigned';
        }
        
        $ticket->update([
            'status' => $status,
            'last_message_at' => now(),
            'last_message_by' => 'partner',
            'unread_for_crm' => true,
        ]);

        // Create notification for CRM (Optional integration here)
        
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'sender_type' => $message->sender_type,
                    'created_at' => $message->created_at->format('d M, H:i'),
                    'is_self' => true
                ]
            ]);
        }

        return back()->with('success', 'Reply sent successfully.');
    }
}
