<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The AI-written commentary shown on the executive dashboard. One row per day,
 * regenerated automatically (see GenerateKpiNarrative) so the dashboard stays
 * "purely updated by AI" without calling the model on every page load.
 */
class KpiNarrative extends Model
{
    protected $fillable = [
        'narrative_date',
        'content',
        'model',
        'input_tokens',
        'output_tokens',
        'status',
        'error',
    ];

    protected $casts = [
        'narrative_date' => 'date',
    ];

    /** The most recent narrative, whatever its status. */
    public static function current(): ?self
    {
        return self::orderByDesc('narrative_date')->first();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed' && filled($this->content);
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
