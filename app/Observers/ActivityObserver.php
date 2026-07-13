<?php

namespace App\Observers;

use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Records create/update/delete events for domain models. Registered against a
 * curated model list in AppServiceProvider so the audit trail covers the
 * records that matter without logging every framework/internal write.
 */
class ActivityObserver
{
    public function created(Model $model): void
    {
        ActivityLogger::model('created', $model);
    }

    public function updated(Model $model): void
    {
        ActivityLogger::model('updated', $model);
    }

    public function deleted(Model $model): void
    {
        ActivityLogger::model('deleted', $model);
    }
}
