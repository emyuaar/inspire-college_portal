<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class OrderInstallment extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'order_installments';

    protected $fillable = [
        'order_id',
        'amount',
        'payment_status', // pending, paid
        'stripe_payment_id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
