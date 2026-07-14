<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetCheckout extends Model
{
    protected $fillable = [
        'asset_id',
        'worker_id',
        'holder_name',
        'checked_out_at',
        'expected_return_at',
        'returned_at',
        'checked_out_by',
        'checked_in_by',
        'notes',
    ];

    protected $casts = [
        'checked_out_at' => 'datetime',
        'expected_return_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    /** Still out with the holder (not yet returned). */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }

    /** Out past its expected return date and not yet returned. */
    public function isOverdue(): bool
    {
        return ! $this->isReturned()
            && $this->expected_return_at !== null
            && $this->expected_return_at->isPast();
    }
}
