<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WhatsappMessage extends Model
{
    protected $fillable = [
        'provider', 'external_id', 'channel_name', 'sender_phone', 'sender_name',
        'body', 'language', 'intent', 'extracted_data', 'confidence', 'status',
        'block_id', 'crop_cycle_id', 'quantity', 'unit', 'event_date',
        'review_note', 'received_at', 'reviewed_by', 'reviewed_at',
        'posted_record_type', 'posted_record_id', 'posted_at',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'confidence' => 'decimal:4',
        'received_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'event_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }

    public function postedRecord(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending_approval');
    }
}
