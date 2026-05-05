<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class CrmUser extends Model
{
    protected $connection = 'mysql_crm';
    protected $table = 'users'; // CRM users table

    // Use standard 'name' column from users table
    // public function getNameAttribute() ... removed
}
