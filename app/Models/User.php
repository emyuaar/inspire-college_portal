<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'org_id',
        'stripe_customer_id',
        'first_name',
        'middle_name',
        'sur_name',
        'email_address',
        'password',
        'status_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Email column ka naam custom hai, helper methods:
    public function getEmailAttribute()
    {
        return $this->email_address;
    }

    // Organization relation (agar learner org se linked hai)
    public function organization()
    {
        return $this->belongsTo(User::class, 'org_id');
    }

    // Organization -> learners
    public function learners()
    {
        return $this->hasMany(User::class, 'org_id');
    }

    // Helper: account type
    public function isOrganization(): bool
    {
        return $this->org_id === $this->id;
    }

    public function isStandaloneLearner(): bool
    {
        return $this->org_id === 0;
    }

    public function isOrgLearner(): bool
    {
        return $this->org_id > 0 && $this->org_id !== $this->id;
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function detail()
    {
        return $this->hasOne(UserDetail::class);
    }
}
