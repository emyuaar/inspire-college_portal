<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SupportTicket extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'support_tickets';

    protected $fillable = [
        'reference',
        'partner_id',
        'subject',
        'category',
        'priority',
        'status',
        'assigned_to',
        'last_message_at',
        'last_message_by',
        'unread_for_crm',
        'unread_for_partner',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'unread_for_crm' => 'boolean',
        'unread_for_partner' => 'boolean',
    ];

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'ticket_id');
    }

    public function logs()
    {
        return $this->hasMany(SupportTicketLog::class, 'ticket_id');
    }

    public function assignee()
    {
        // This is a CRM user, so in portal we use custom model
        return $this->belongsTo(CrmUser::class, 'assigned_to');
    }

    public function isClosed()
    {
        return in_array($this->status, ['resolved', 'closed']);
    }
}
