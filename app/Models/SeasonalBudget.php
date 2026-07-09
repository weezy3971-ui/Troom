<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeasonalBudget extends Model
{
    protected $fillable = [
        'crop_cycle_id',
        'labour_budget',
        'input_budget',
        'irrigation_budget',
        'overhead_budget',
        'total_budget',
    ];

    protected $casts = [
        'labour_budget' => 'decimal:2',
        'input_budget' => 'decimal:2',
        'irrigation_budget' => 'decimal:2',
        'overhead_budget' => 'decimal:2',
        'total_budget' => 'decimal:2',
    ];

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }
}
