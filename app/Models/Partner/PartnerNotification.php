<?php

namespace App\Models\Partner;

use Illuminate\Database\Eloquent\Model;

class PartnerNotification extends Model
{
    protected $table = 'partner_notifications';

    protected $fillable = [
        'partner_id',
        'type',
        'title',
        'body',
        'icon',
        'color',
        'action_url',
        'action_label',
        'related_id',
        'related_type',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeForPartner($query, int $partnerId)
    {
        return $query->where('partner_id', $partnerId);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markRead(): void
    {
        if ($this->isUnread()) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Return human-readable relative time.
     */
    public function relativeTime(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Map icon key to Font-Awesome class.
     */
    public function iconClass(): string
    {
        return match ($this->icon) {
            'check'    => 'fa-solid fa-circle-check',
            'warning'  => 'fa-solid fa-triangle-exclamation',
            'error'    => 'fa-solid fa-circle-xmark',
            'clock'    => 'fa-solid fa-clock',
            'diploma'  => 'fa-solid fa-graduation-cap',
            'money'    => 'fa-solid fa-sterling-sign',
            'user'     => 'fa-solid fa-user',
            'calendar' => 'fa-solid fa-calendar-days',
            default    => 'fa-solid fa-bell',
        };
    }

    /**
     * Map color key to Tailwind bg/text classes.
     */
    public function colorClasses(): array
    {
        return match ($this->color) {
            'red'    => ['bg' => 'bg-red-100',   'text' => 'text-red-600'],
            'green'  => ['bg' => 'bg-green-100', 'text' => 'text-green-600'],
            'amber'  => ['bg' => 'bg-amber-100', 'text' => 'text-amber-600'],
            'blue'   => ['bg' => 'bg-blue-100',  'text' => 'text-blue-600'],
            'pink'   => ['bg' => 'bg-pink-100',  'text' => 'text-pink-600'],
            default  => ['bg' => 'bg-slate-100', 'text' => 'text-slate-500'],
        };
    }
}
