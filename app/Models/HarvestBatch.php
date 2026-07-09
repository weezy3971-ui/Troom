<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HarvestBatch extends Model
{
    protected $fillable = [
        'crop_cycle_id',
        'block_id',
        'harvest_date',
        'quantity_kg',
        'quality_grade',
        'rejects_kg',
        'harvested_by',
    ];

    protected $casts = [
        'harvest_date' => 'date',
        'quantity_kg' => 'decimal:2',
        'rejects_kg' => 'decimal:2',
    ];

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function harvestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'harvested_by');
    }

    public function packhouseLots(): HasMany
    {
        return $this->hasMany(PackhouseLot::class);
    }

    /**
     * Net saleable weight after rejects.
     */
    public function netQuantity(): float
    {
        return (float) $this->quantity_kg - (float) $this->rejects_kg;
    }

    /**
     * Weight already committed to packhouse lots.
     */
    public function packedQuantity(): float
    {
        return (float) $this->packhouseLots->sum('quantity_packed');
    }

    /**
     * Remaining weight available to pack.
     */
    public function availableToPack(): float
    {
        return $this->netQuantity() - $this->packedQuantity();
    }
}
