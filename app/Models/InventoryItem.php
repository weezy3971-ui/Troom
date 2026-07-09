<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $fillable = [
        'farm_id',
        'name',
        'category',
        'unit',
        'reorder_level',
    ];

    protected $casts = [
        'reorder_level' => 'decimal:2',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Current stock on hand: receipts and positive adjustments add,
     * issues subtract.
     */
    public function currentStock(): float
    {
        return (float) $this->transactions->sum(fn ($t) => $t->signedQuantity());
    }

    /**
     * Business rule: a low_inventory alert fires when stock falls below the
     * item's reorder level.
     */
    public function isLowStock(): bool
    {
        return $this->currentStock() < (float) $this->reorder_level;
    }
}
