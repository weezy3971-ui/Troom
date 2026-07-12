<?php

namespace App\Providers;

use App\Observers\ActivityObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Domain models whose create/update/delete events are captured in the
     * audit trail. Deliberately excludes ActivityLog itself and high-volume
     * auto-generated records (e.g. cost allocations, KPI snapshots).
     */
    protected array $auditedModels = [
        \App\Models\Farm::class,
        \App\Models\Block::class,
        \App\Models\Crop::class,
        \App\Models\Asset::class,
        \App\Models\CropCycle::class,
        \App\Models\SeasonalBudget::class,
        \App\Models\NurseryBatch::class,
        \App\Models\Planting::class,
        \App\Models\HarvestBatch::class,
        \App\Models\PackhouseLot::class,
        \App\Models\QualityCheck::class,
        \App\Models\InventoryItem::class,
        \App\Models\InventoryTransaction::class,
        \App\Models\Customer::class,
        \App\Models\SalesOrder::class,
        \App\Models\Dispatch::class,
        \App\Models\User::class,
        \App\Models\ApprovedEmail::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ($this->auditedModels as $model) {
            $model::observe(ActivityObserver::class);
        }
    }
}
