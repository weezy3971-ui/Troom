<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PackhouseLot extends Model
{
    protected $fillable = [
        'harvest_batch_id',
        'lot_number',
        'pack_date',
        'quantity_packed',
        'packaging_type',
        'traceability_code',
    ];

    protected $casts = [
        'pack_date' => 'date',
        'quantity_packed' => 'decimal:2',
    ];

    public function harvestBatch(): BelongsTo
    {
        return $this->belongsTo(HarvestBatch::class);
    }

    public function qualityChecks(): HasMany
    {
        return $this->hasMany(QualityCheck::class);
    }

    public function latestQualityCheck(): HasOne
    {
        return $this->hasOne(QualityCheck::class)->latestOfMany('check_date');
    }

    public function salesOrderLines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    /**
     * A lot is quality-passed when its most recent check resulted in a pass.
     */
    public function isQualityPassed(): bool
    {
        return optional($this->latestQualityCheck)->result === 'pass';
    }

    /**
     * A lot has failed when its most recent check resulted in a fail.
     * Failed lots cannot be added to sales order lines until re-graded.
     */
    public function isQualityFailed(): bool
    {
        return optional($this->latestQualityCheck)->result === 'fail';
    }
}
