<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Outgrower extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'location',
        'notes',
        'specialization',
        'reliability_rating',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'reliability_rating' => 'integer',
    ];

    public function salesOrderLines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    public function totalQuantitySupplied(): float
    {
        return (float) $this->salesOrderLines()->sum('quantity');
    }

    public function totalRevenue(): float
    {
        return (float) $this->salesOrderLines()->selectRaw('SUM(quantity * unit_price) as total')->value('total');
    }
}
