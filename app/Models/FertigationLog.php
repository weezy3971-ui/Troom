<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FertigationLog extends Model
{
    protected $fillable = [
        'crop_cycle_id',
        'log_date',
        'nutrient_type',
        'quantity',
        'method',
        'cost',
        'logged_by',
    ];

    protected $casts = [
        'log_date' => 'date',
        'quantity' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
