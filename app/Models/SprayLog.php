<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SprayLog extends Model
{
    protected $fillable = [
        'crop_cycle_id',
        'log_date',
        'chemical_used',
        'target_pest',
        'quantity',
        'pre_harvest_interval_days',
        'applicator_id',
        'photo_path',
    ];

    protected $casts = [
        'log_date' => 'date',
        'quantity' => 'decimal:2',
        'pre_harvest_interval_days' => 'integer',
    ];

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }

    public function applicator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicator_id');
    }

    /**
     * Date on which this spray's pre-harvest interval clears.
     */
    public function phiEndsOn(): Carbon
    {
        return $this->log_date->copy()->addDays($this->pre_harvest_interval_days);
    }

    /**
     * Whether this spray's pre-harvest interval window is still active today.
     */
    public function isPhiActive(): bool
    {
        return $this->phiEndsOn()->isFuture();
    }
}
