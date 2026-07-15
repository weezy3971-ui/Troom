<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HarvestByProduct extends Model
{
    protected $fillable = [
        'harvest_batch_id',
        'name',
        'quantity_kg',
        'notes',
    ];

    protected $casts = [
        'quantity_kg' => 'decimal:2',
    ];

    public function harvestBatch(): BelongsTo
    {
        return $this->belongsTo(HarvestBatch::class);
    }
}
