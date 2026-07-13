<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyActivity extends Model
{
    /**
     * Activity type is selected from a controlled list, not free text,
     * to keep reporting consistent.
     */
    public const TYPES = [
        'land_preparation',
        'planting',
        'weeding',
        'scouting',
        'spraying',
        'fertigation',
        'pruning',
        'harvesting',
        'general',
    ];

    /**
     * Types tied to compliance require photo + GPS capture.
     */
    public const COMPLIANCE_TYPES = [
        'spraying',
    ];

    protected $fillable = [
        'block_id',
        'crop_cycle_id',
        'activity_type',
        'activity_date',
        'description',
        'logged_by',
        'photo_path',
        'gps_location',
        'client_uuid',
    ];

    protected $casts = [
        'activity_date' => 'date',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function requiresCompliance(): bool
    {
        return in_array($this->activity_type, self::COMPLIANCE_TYPES, true);
    }
}
