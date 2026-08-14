<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name', 'last_name', 'birth_date', 'gender', 'life_status', 'parent_id', 'secondary_parent_id',
        'address', 'google_maps_url', 'facebook_url', 'instagram_url', 'tiktok_url', 'x_url', 'spouse_id',
        'profile_image_path',
        'contact_number', 'email',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function spouse(): BelongsTo
    {
        return $this->belongsTo(self::class, 'spouse_id');
    }

    public function secondaryParent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'secondary_parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('birth_date');
    }
}
