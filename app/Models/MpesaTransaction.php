<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MpesaTransaction extends Model
{
    public const DIRECTIONS = ['c2b', 'b2c'];

    public const STATUSES = ['pending', 'success', 'failed'];

    protected $fillable = [
        'direction',
        'phone',
        'amount',
        'account_reference',
        'mpesa_receipt_number',
        'checkout_request_id',
        'conversation_id',
        'originator_conversation_id',
        'status',
        'result_description',
        'raw_payload',
        'payable_type',
        'payable_id',
        'initiated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'raw_payload' => 'array',
    ];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /** A C2B payment that arrived but matched no order — needs manual allocation. */
    public function isUnallocated(): bool
    {
        return $this->direction === 'c2b' && $this->isSuccess() && $this->payable_type === null;
    }
}
