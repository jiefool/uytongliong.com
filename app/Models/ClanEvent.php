<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ClanEvent extends Model
{
    protected $fillable = ['title', 'slug', 'event_date', 'location', 'description', 'poster_image_path', 'user_id'];

    protected function casts(): array
    {
        return ['event_date' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            if ($event->slug) return;

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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
