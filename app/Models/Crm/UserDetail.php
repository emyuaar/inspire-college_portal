<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'user_detail'; // existing table

    protected $fillable = [
        'learner_id',
        'contact',
        'dob',
        'gender',
        'address_line_1',
        'address_line_2',
        'country',
        'city',
        'state',
        'zip_code',
    ];
}
