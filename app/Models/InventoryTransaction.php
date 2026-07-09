<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'farm_id',
        'crop_cycle_id',
        'type',
        'quantity',
        'transaction_date',
        'reference',
        'cost',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'quantity' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }

    /**
     * Quantity signed by direction: issues reduce stock, receipts and
     * adjustments increase it.
     */
    public function signedQuantity(): float
    {
        return $this->type === 'issue'
            ? -(float) $this->quantity
            : (float) $this->quantity;
    }
}
