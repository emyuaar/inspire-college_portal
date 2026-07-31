<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'order_details';

    protected $fillable = [
        'order_id',
        'course_id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
