<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAssignment extends Model
{
    protected $fillable = [
        'project_task_id',
        'worker_id',
        'assigned_date',
        'hours',
        'rate',
        'cost',
        'note',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'hours' => 'decimal:2',
        'rate' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
