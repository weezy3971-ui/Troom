<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    /**
     * Store stage: separates pre-harvest inputs from post-harvest packaging,
     * label keyed by stored value.
     */
    public const STAGES = [
        'pre_harvest_input'      => 'Pre-harvest input',
        'post_harvest_packaging' => 'Post-harvest packaging',
        'general'                => 'General',
    ];

    protected $fillable = [
        'farm_id',
        'name',
        'category',
        'stage',
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

    /** Human-readable label for this item's store stage. */
    public function stageLabel(): string
    {
        return self::STAGES[$this->stage] ?? 'General';
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
