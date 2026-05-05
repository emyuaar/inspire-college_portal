<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Promotion extends Model
{
    protected $connection = 'mysql_website';
    protected $table = 'promotions';
    
    protected $casts = [
        'discount_percent' => 'integer',
        'starts_at'        => 'datetime',
        'ends_at'          => 'datetime',
        'is_active'        => 'boolean',
    ];

    public function scopeActiveNow(Builder $q): Builder
    {
        $now = now();

        return $q->where('promotions.is_active', 1)
            ->where(function ($qq) use ($now) {
                $qq->whereNull('promotions.starts_at')->orWhere('promotions.starts_at', '<=', $now);
            })
            ->where(function ($qq) use ($now) {
                $qq->whereNull('promotions.ends_at')->orWhere('promotions.ends_at', '>=', $now);
            });
    }
}
