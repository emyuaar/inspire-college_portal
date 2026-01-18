<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class EnrolmentStatus extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'enrolment_status'; // Singular table name usually in CRM legacy

    protected $fillable = [
        'status',
    ];
}
