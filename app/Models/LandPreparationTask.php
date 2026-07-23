<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One step of a land-preparation round, copied from the standard checklist when
 * the round is started.
 *
 * A step can be skipped as well as done — not every block needs liming or
 * drainage work — and a skipped step is not counted as outstanding, so a round
 * can complete honestly without pretending work happened.
 */
class LandPreparationTask extends Model
{
    public const STATUSES = [
        'pending' => 'Pending',
        'done' => 'Done',
        'skipped' => 'Not needed',
    ];

    protected $fillable = [
        'land_preparation_id',
        'sequence',
        'name',
        'description',
        'status',
        'done_on',
        'notes',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'done_on' => 'date',
    ];

    public function landPreparation(): BelongsTo
    {
        return $this->belongsTo(LandPreparation::class);
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
