<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    protected $fillable = [
        'farm_id',
        'name',
        'type',
        'purchase_date',
        'status',
        'current_hours',
        'current_mileage',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'current_hours' => 'decimal:2',
        'current_mileage' => 'decimal:2',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(AssetStatusHistory::class);
    }

    public function irrigationLogs(): HasMany
    {
        return $this->hasMany(IrrigationLog::class, 'pump_asset_id');
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class, 'vehicle_asset_id');
    }

    public function isOperational(): bool
    {
        return $this->status === 'operational';
    }

    /**
     * Update status and log the change in the audit trail.
     * Status history is never overwritten per spec.
     */
    public function updateStatus(string $newStatus, ?int $changedBy = null): void
    {
        $oldStatus = $this->status;

        if ($oldStatus === $newStatus) {
            return;
        }

        $this->update(['status' => $newStatus]);

        $this->statusHistories()->create([
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }
}
