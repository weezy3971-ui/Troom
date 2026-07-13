<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NurseryBatch extends Model
{
    protected $fillable = [
        'crop_id',
        'sow_date',
        'expected_ready_date',
        'quantity',
        'status',
    ];

    protected $casts = [
        'sow_date' => 'date',
        'expected_ready_date' => 'date',
        'quantity' => 'integer',
    ];

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function plantings(): HasMany
    {
        return $this->hasMany(Planting::class);
    }

    /**
     * Quantity still available to transplant.
     */
    public function remainingQuantity(): int
    {
        return $this->quantity - $this->plantings()->sum('quantity');
    }

    /**
     * A batch's status auto-updates to reflect planting state:
     * - transplanted: once any Planting is recorded.
     * - ready:        reverts to ready if all plantings are removed.
     */
    public function syncTransplantStatus(): void
    {
        $hasPlantings = $this->plantings()->exists();

        if ($hasPlantings && $this->status !== 'transplanted') {
            $this->update(['status' => 'transplanted']);
        } elseif (! $hasPlantings && $this->status === 'transplanted') {
            $this->update(['status' => 'ready']);
        }
    }
}
