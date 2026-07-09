<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostAllocation extends Model
{
    protected $fillable = [
        'crop_cycle_id',
        'block_id',
        'source_type',
        'source_id',
        'amount',
        'allocation_date',
        'description',
    ];

    protected $casts = [
        'allocation_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }
}
