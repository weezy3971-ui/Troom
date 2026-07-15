<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CropProgramStage extends Model
{
    protected $fillable = [
        'crop_program_id',
        'sequence',
        'name',
        'activity_type',
        'offset_days',
        'cadence',
        'default_inputs',
        'notes',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'offset_days' => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(CropProgram::class, 'crop_program_id');
    }
}
