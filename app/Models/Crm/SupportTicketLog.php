<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class SupportTicketLog extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'support_ticket_logs';

    protected $fillable = [
        'ticket_id',
        'action',
        'from_value',
        'to_value',
        'actor_type',
        'actor_id',
        'note',
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function actor()
    {
        if ($this->actor_type === 'crm_user') {
            return $this->belongsTo(CrmUser::class, 'actor_id');
        }
        return null; // System actions or other
    }
}
