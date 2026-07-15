<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GerminationCheck extends Model
{
    protected $fillable = [
        'crop_cycle_id',
        'check_date',
        'days_after_sowing',
        'sample_size',
        'germinated_count',
        'germination_rate',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'check_date' => 'date',
        'days_after_sowing' => 'integer',
        'sample_size' => 'integer',
        'germinated_count' => 'integer',
        'germination_rate' => 'decimal:3',
    ];

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
