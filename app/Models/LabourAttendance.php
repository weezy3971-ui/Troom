<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabourAttendance extends Model
{
    protected $fillable = [
        'attendance_date',
        'worker_name',
        'block_id',
        'crop_cycle_id',
        'task',
        'hours_worked',
        'rate',
        'cost',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'hours_worked' => 'decimal:2',
        'rate' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }
}
