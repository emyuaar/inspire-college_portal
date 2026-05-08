<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderInstallment extends Model
{
    use SoftDeletes;
    protected $connection = 'mysql_crm';
    protected $table = 'order_installments';

    protected $fillable = [
        'order_id',
        'amount',
        'payment_status', // pending, paid
        'stripe_payment_id',
        'installment_no',
        'due_date',
        'paid_at',
        'amount_paid',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
