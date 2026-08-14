<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class TreeAccessPin extends Model
{
    protected $fillable = ['label', 'pin_hash', 'pin_encrypted', 'user_id'];

    protected $hidden = ['pin_hash'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function displayPin(): ?string
    {
        return $this->pin_encrypted ? Crypt::decryptString($this->pin_encrypted) : null;
    }
}
