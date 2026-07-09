<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IrrigationLog extends Model
{
    protected $fillable = [
        'block_id',
        'pump_asset_id',
        'log_date',
        'start_time',
        'end_time',
        'hours',
        'water_volume',
        'logged_by',
    ];

    protected $casts = [
        'log_date' => 'date',
        'hours' => 'decimal:2',
        'water_volume' => 'decimal:2',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function pump(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'pump_asset_id');
    }

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
