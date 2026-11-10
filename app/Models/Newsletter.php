<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Newsletter extends Model
{
    protected $fillable = ['title', 'issue_date', 'summary', 'body', 'cover_image_path', 'status'];

    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'published_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $newsletter): void {
            // The suffix also keeps titles such as "manage" clear of reserved routes.
            $newsletter->slug = (Str::slug($newsletter->title) ?: 'newsletter').'-'.Str::lower(Str::random(10));
        });

        static::saving(function (self $newsletter): void {
            if ($newsletter->status === 'published' && ! $newsletter->published_at) {
                $newsletter->published_at = now();
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function canManage(?User $user): bool
    {
        return $user && ($user->isAdmin() || $user->id === $this->user_id);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
