<?php

namespace App\Models;

use App\Support\LandPrepProgram;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A round of land preparation on a block.
 *
 * Preparation is the step between "a block exists" and "something is planted in
 * it", which is why it hangs off the block rather than off a crop cycle: at the
 * time the work is done and paid for, no cycle exists yet. Linking it to the
 * cycle that follows is what stops its cost being filed as an expense that
 * belongs to no stage of the planting.
 */
class LandPreparation extends Model
{
    public const STATUSES = [
        'planned' => 'Planned',
        'in_progress' => 'In Progress',
        'complete' => 'Complete',
        'not_required' => 'Not Required',
    ];

    protected $fillable = [
        'block_id',
        'crop_cycle_id',
        'status',
        'started_on',
        'completed_on',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'started_on' => 'date',
        'completed_on' => 'date',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(LandPreparationTask::class)->orderBy('sequence');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Start a preparation round with a copy of the generic checklist. The tasks
     * are copied, not referenced, so revising the standard list later can never
     * rewrite what a supervisor already ticked off in the field.
     */
    public static function startFor(Block $block, ?int $userId = null): self
    {
        $prep = self::create([
            'block_id' => $block->id,
            'status' => 'planned',
            'created_by' => $userId,
        ]);

        foreach (LandPrepProgram::tasks() as $i => $task) {
            $prep->tasks()->create([
                'sequence' => $i + 1,
                'name' => $task['name'],
                'description' => $task['description'],
            ]);
        }

        return $prep;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'complete' => 'active',
            'in_progress' => 'info',
            'not_required' => 'neutral',
            default => 'neutral',
        };
    }

    /**
     * Attach a preparation round to a freshly-created cycle.
     *
     * A block prepared last week should have its costs pulled into this
     * planting rather than stranded, so an existing unattributed round on the
     * block is reused before a new one is started.
     */
    public static function attachTo(CropCycle $cycle, ?int $userId = null): self
    {
        $prep = self::where('block_id', $cycle->block_id)
            ->whereNull('crop_cycle_id')
            ->latest('id')
            ->first() ?? self::startFor($cycle->block, $userId);

        $prep->update(['crop_cycle_id' => $cycle->id]);

        return $prep;
    }

    /**
     * Whether this round no longer stands in the way of planting — either the
     * work is done, or someone recorded that this block didn't need it.
     */
    public function isSatisfied(): bool
    {
        return in_array($this->status, ['complete', 'not_required'], true);
    }

    public function doneCount(): int
    {
        return $this->tasks->where('status', 'done')->count();
    }

    /** Tasks that still need doing — skipped ones don't count against progress. */
    public function outstandingCount(): int
    {
        return $this->tasks->where('status', 'pending')->count();
    }

    public function percentComplete(): int
    {
        $total = $this->tasks->whereIn('status', ['done', 'pending'])->count();

        return $total === 0 ? 100 : (int) round($this->doneCount() / $total * 100);
    }

    public function totalCost(): float
    {
        return (float) $this->expenses()->sum('amount');
    }

    /** Whether the block is ready for a crop cycle to be planted into it. */
    public function isComplete(): bool
    {
        return $this->status === 'complete';
    }
}
