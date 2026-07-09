<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Crop extends Model
{
    protected $fillable = [
        'name',
        'variety',
        'crop_type',
        'days_to_maturity',
        'expected_yield_per_acre',
    ];

    protected $casts = [
        'days_to_maturity' => 'integer',
        'expected_yield_per_acre' => 'decimal:2',
    ];

    public function cropCycles(): HasMany
    {
        return $this->hasMany(CropCycle::class);
    }

    public function nurseryBatches(): HasMany
    {
        return $this->hasMany(NurseryBatch::class);
    }
}
