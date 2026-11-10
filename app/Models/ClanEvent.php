<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ClanEvent extends Model
{
    protected $fillable = ['title', 'slug', 'event_date', 'location', 'description', 'poster_image_path', 'user_id', 'status', 'start_time', 'end_time', 'timezone'];

    protected $attributes = ['status' => 'published', 'timezone' => 'Asia/Manila'];

    protected function casts(): array
    {
        return ['event_date' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            if ($event->slug) {
                return;
            }

            $base = Str::slug($event->title) ?: 'clan-event';
            $slug = $base;
            $suffix = 2;

            while (self::query()->where('slug', $slug)->exists()) {
                $slug = "{$base}-{$suffix}";
                $suffix++;
            }

            $event->slug = $slug;
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function canManage(?User $user): bool
    {
        return $user && ($user->isAdmin() || $user->id === $this->user_id);
    }

    public function acceptsRsvps(): bool
    {
        $closingTime = Carbon::parse($this->event_date->format('Y-m-d').' '.($this->end_time ?: $this->start_time ?: '23:59:59'), $this->timezone);

        return $this->status === 'published' && $closingTime->isFuture();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
