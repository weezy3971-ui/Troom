<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Horse extends Model
{
    protected $fillable = [
        'name',
        'breed',
        'rest_minutes',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'rest_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function rides(): HasMany
    {
        return $this->hasMany(HorseRide::class);
    }

    /**
     * Assigned (not cancelled) rides for this horse — the basis for
     * availability and rest calculations.
     */
    protected function activeRides()
    {
        return $this->rides()
            ->whereIn('status', ['assigned', 'completed'])
            ->get();
    }

    /**
     * The ride this horse is out on at a given moment, if any.
     */
    public function rideAt(?Carbon $at = null): ?HorseRide
    {
        $at ??= now();

        return $this->activeRides()->first(
            fn (HorseRide $r) => $at->between($r->start_time, $r->end_time)
        );
    }

    /**
     * The moment this horse becomes free again (end of its current ride plus
     * the rest period), or null if it is already available.
     */
    public function busyUntil(?Carbon $at = null): ?Carbon
    {
        $at ??= now();

        // The ride whose window (start .. end + rest) currently contains $at.
        $window = $this->activeRides()->first(function (HorseRide $r) use ($at) {
            $restEnd = $r->end_time->copy()->addMinutes($this->rest_minutes);
            return $at->betweenIncluded($r->start_time, $restEnd);
        });

        return $window
            ? $window->end_time->copy()->addMinutes($this->rest_minutes)
            : null;
    }

    /**
     * Computed status right now: on_ride, resting, or available.
     */
    public function currentStatus(?Carbon $at = null): string
    {
        $at ??= now();

        if ($this->rideAt($at)) {
            return 'on_ride';
        }

        return $this->busyUntil($at) ? 'resting' : 'available';
    }

    /**
     * Whether this horse can take a new ride over [start, end] — it must be
     * free (including its rest buffer) across that whole window.
     */
    public function isFreeFor(Carbon $start, Carbon $end): bool
    {
        if (! $this->is_active) {
            return false;
        }

        foreach ($this->activeRides() as $r) {
            $restEnd = $r->end_time->copy()->addMinutes($this->rest_minutes);
            // Overlap test between [start, end] and [r.start, r.end + rest].
            if ($start->lessThan($restEnd) && $r->start_time->lessThan($end)) {
                return false;
            }
        }

        return true;
    }
}
