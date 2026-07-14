<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Planting extends Model
{
    protected $fillable = [
        'nursery_batch_id',
        'crop_cycle_id',
        'quantity',
        'bed_count',
        'seeds_sown',
        'area_acres',
        'planting_date',
    ];

    protected $casts = [
        'planting_date' => 'date',
        'quantity' => 'integer',
        'bed_count' => 'integer',
        'seeds_sown' => 'integer',
        'area_acres' => 'decimal:2',
    ];

    public function nurseryBatch(): BelongsTo
    {
        return $this->belongsTo(NurseryBatch::class);
    }

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }
}
