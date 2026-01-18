<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'orders';

    protected $fillable = [
        'learner_id',
        'enrolment_id',
        'amount',
        'stripe_payment_id',
        'status_id',
    ];

    public function learner()
    {
        return $this->belongsTo(\App\Models\User::class, 'learner_id');
    }

    public function enrolment()
    {
        return $this->belongsTo(Enrolment::class, 'enrolment_id');
    }

    public function installments()
    {
        return $this->hasMany(OrderInstallment::class, 'order_id');
    }
}
