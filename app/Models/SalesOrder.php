<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesOrder extends Model
{
    protected $fillable = [
        'customer_id',
        'crop_id',
        'order_date',
        'requested_quantity',
        'status',
        'delivery_date',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'requested_quantity' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    public function dispatch(): HasOne
    {
        return $this->hasOne(Dispatch::class);
    }

    /**
     * Total quality-passed quantity allocated to this order.
     */
    public function allocatedQuantity(): float
    {
        return (float) $this->lines->sum('quantity');
    }

    /**
     * Order value from its allocated lines.
     */
    public function orderValue(): float
    {
        return (float) $this->lines->sum(fn ($line) => $line->lineTotal());
    }

    /**
     * Business rule: an order_at_risk alert fires when a delivery date is
     * approaching (within 7 days) without sufficient quality-passed lot
     * quantity allocated.
     */
    public function isAtRisk(): bool
    {
        if (in_array($this->status, ['fulfilled', 'cancelled', 'dispatched'])) {
            return false;
        }

        if (! $this->delivery_date) {
            return false;
        }

        // "Approaching" means the delivery date is in the future but within 7 days.
        $approaching = $this->delivery_date->isFuture()
            && $this->delivery_date->isBefore(now()->addDays(7));

        $underAllocated = $this->allocatedQuantity() < (float) $this->requested_quantity;

        return $approaching && $underAllocated;
    }
}
