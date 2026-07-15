<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderLine extends Model
{
    protected $fillable = [
        'sales_order_id',
        'packhouse_lot_id',
        'source',
        'outgrower_id',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function packhouseLot(): BelongsTo
    {
        return $this->belongsTo(PackhouseLot::class);
    }

    public function outgrower(): BelongsTo
    {
        return $this->belongsTo(Outgrower::class);
    }

    public function isFromOutgrower(): bool
    {
        return $this->source === 'outgrower';
    }

    /** Human-readable source label for the line. */
    public function sourceLabel(): string
    {
        return $this->isFromOutgrower()
            ? 'Outgrower: ' . ($this->outgrower?->name ?? '—')
            : 'Lot #' . ($this->packhouse_lot_id ?? '—');
    }

    public function lineTotal(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }
}
