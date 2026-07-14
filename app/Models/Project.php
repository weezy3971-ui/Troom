<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'code',
        'project_type',
        'description',
        'status',
        'farm_id',
        'block_id',
        'crop_cycle_id',
        'start_date',
        'end_date',
        'budget',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function cropCycle(): BelongsTo
    {
        return $this->belongsTo(CropCycle::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function costAllocations(): HasMany
    {
        return $this->hasMany(CostAllocation::class);
    }

    /**
     * All labour assignments across this project's tasks.
     */
    public function assignments()
    {
        return $this->hasManyThrough(TaskAssignment::class, ProjectTask::class);
    }

    /**
     * Total money allocated against this project (labour + inputs + other).
     */
    public function totalSpend(): float
    {
        return (float) $this->costAllocations()->sum('amount');
    }
}
