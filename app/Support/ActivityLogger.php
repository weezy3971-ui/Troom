<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Central place for writing audit-trail entries. Domain model create/update/
 * delete events are captured automatically via ActivityObserver; auth events
 * and other one-offs call log() directly.
 */
class ActivityLogger
{
    /**
     * Attributes that are noise in an audit trail, plus government ID numbers —
     * the trail records who changed what, and doesn't need to copy a worker's
     * national ID out of their record to do it.
     */
    protected const IGNORED = [
        'updated_at', 'created_at', 'password', 'remember_token',
        'national_id', 'worker_national_id',
    ];

    /**
     * Domain action currently in effect, set by as().
     */
    protected static ?string $override = null;

    /**
     * Record model updates made inside $callback under a domain action name
     * rather than the generic "updated" — so checking a tool back in reads
     * "Checked in Asset Checkout: Daniel Mutua", not "Updated Asset Checkout".
     *
     * Only updates are relabelled. Records *created* inside the callback keep
     * their own "created" action, so wrapping a block that does both stays
     * accurate.
     */
    public static function as(string $action, callable $callback): mixed
    {
        $previous = self::$override;
        self::$override = $action;

        try {
            return $callback();
        } finally {
            self::$override = $previous;
        }
    }

    /**
     * Record an arbitrary activity.
     */
    public static function log(string $action, ?Model $subject = null, ?string $description = null, array $properties = []): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description ?? Str::headline($action),
            'properties' => $properties ?: null,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Record a model mutation. Only logged when a signed-in user is acting, so
     * database seeding and background bootstrapping don't flood the trail.
     */
    public static function model(string $action, Model $model): void
    {
        if (! Auth::check()) {
            return;
        }

        // Keep the attribute diff against the real action before relabelling.
        $properties = self::changes($action, $model);

        if ($action === 'updated' && self::$override) {
            $action = self::$override;
        }

        $type = Str::headline(class_basename($model));
        $label = self::label($model);
        $verb = ucfirst(str_replace('_', ' ', $action));
        $description = $verb . ' ' . $type . ($label ? ": {$label}" : '');

        self::log($action, $model, $description, $properties);
    }

    /**
     * A short human label for a record, pulled from whichever common naming
     * field the model happens to have.
     */
    public static function label(Model $model): ?string
    {
        $fields = [
            // Generic naming fields
            'name', 'season_name', 'title', 'reference',
            // Traceable records
            'lot_number', 'traceability_code', 'receipt_number',
            // People-centric records (labour, custody, rides)
            'worker_name', 'holder_name', 'customer_name',
            // Activity-centric records with no name of their own
            'activity_type', 'chemical_used', 'task', 'category',
            'email',
        ];

        foreach ($fields as $field) {
            if (! empty($model->{$field})) {
                return (string) $model->{$field};
            }
        }

        return '#' . $model->getKey();
    }

    /**
     * The meaningful attribute changes for this event.
     */
    protected static function changes(string $action, Model $model): array
    {
        if ($action === 'updated') {
            return collect($model->getChanges())
                ->except(self::IGNORED)
                ->all();
        }

        if ($action === 'created') {
            return collect($model->getAttributes())
                ->except(array_merge(self::IGNORED, ['id']))
                ->all();
        }

        return [];
    }
}
