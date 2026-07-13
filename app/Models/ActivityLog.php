<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Short, human-friendly name of the affected record type (e.g. "Crop Cycle").
     */
    public function subjectLabel(): ?string
    {
        return $this->subject_type ? Str::headline(class_basename($this->subject_type)) : null;
    }

    /**
     * Colour keyword used by the UI badge for this action.
     */
    public function actionColor(): string
    {
        return match ($this->action) {
            'created', 'registered', 'signed_in' => 'success',
            'deleted', 'deactivated' => 'danger',
            'updated', 'activated' => 'info',
            default => 'neutral',
        };
    }
}
