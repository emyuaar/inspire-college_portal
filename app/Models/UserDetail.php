<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserDetail extends Model
{
    use SoftDeletes;

    protected $table = 'user_details';

    protected $fillable = [
        'user_id', 'gender', 'd_o_b', 'address', 'country', 'city', 'state',
    ];

    protected $casts = [
        'd_o_b' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
