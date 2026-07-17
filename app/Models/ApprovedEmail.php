<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovedEmail extends Model
{
    protected $fillable = [
        'email',
        'phone',
        'role',
        'invited_by',
        'registered_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
    ];

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Whether the invited person has already created their account.
     */
    public function isRegistered(): bool
    {
        return $this->registered_at !== null;
    }
}
