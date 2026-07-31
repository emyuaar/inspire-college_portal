<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountSetupToken extends Model
{
    protected $connection = 'mysql_portal';

    protected $fillable = [
        'user_id',
        'email',
        'token_hash',
        'legacy',
        'used_at',
        'revoked_at',
        'created_by',
    ];

    protected $casts = [
        'legacy' => 'boolean',
        'used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];
}
