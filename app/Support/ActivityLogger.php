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
     * Attributes that are noise in an audit trail and never worth recording.
     */
    protected const IGNORED = ['updated_at', 'created_at', 'password', 'remember_token'];

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

        $type = Str::headline(class_basename($model));
        $label = self::label($model);
        $description = ucfirst($action) . ' ' . $type . ($label ? ": {$label}" : '');

        self::log($action, $model, $description, self::changes($action, $model));
    }

    /**
     * A short human label for a record, pulled from whichever common naming
     * field the model happens to have.
     */
    public static function label(Model $model): ?string
    {
        foreach (['name', 'season_name', 'lot_number', 'traceability_code', 'title', 'reference', 'email'] as $field) {
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
