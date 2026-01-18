<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class PaymentPlan extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'payment_plans';

    protected $fillable = [
        'title',
    ];
}
