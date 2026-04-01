<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SupportMessage extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'support_messages';

    protected $fillable = [
        'ticket_id',
        'sender_type',
        'sender_id',
        'body',
        'is_internal_note',
        'attachment_path',
        'attachment_name',
    ];

    protected $casts = [
        'is_internal_note' => 'boolean',
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function sender()
    {
        // Polymorphic or manual sender logic. Usually for partner = User, for crm = CrmUser
        if ($this->sender_type === 'partner') {
            return $this->belongsTo(User::class, 'sender_id');
        } elseif ($this->sender_type === 'crm_user') {
            return $this->belongsTo(CrmUser::class, 'sender_id');
        }
        return null;
    }
}
