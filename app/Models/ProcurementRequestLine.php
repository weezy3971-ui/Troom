<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementRequestLine extends Model
{
    protected $fillable = [
        'procurement_request_id',
        'inventory_item_id',
        'item_name',
        'quantity',
        'unit',
        'estimated_cost',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class, 'procurement_request_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
