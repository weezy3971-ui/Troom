<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispatch extends Model
{
    protected $fillable = [
        'sales_order_id',
        'vehicle_asset_id',
        'driver_id',
        'dispatch_date',
        'route',
        'status',
    ];

    protected $casts = [
        'dispatch_date' => 'date',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'vehicle_asset_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
