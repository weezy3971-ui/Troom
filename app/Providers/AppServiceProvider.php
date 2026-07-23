<?php

namespace App\Providers;

use App\Observers\ActivityObserver;
use App\Services\Mpesa\DarajaGateway;
use App\Services\Mpesa\FakeMpesaGateway;
use App\Services\Mpesa\MpesaGatewayContract;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Domain models whose create/update/delete events are captured in the
     * audit trail. Deliberately excludes ActivityLog itself, child line records
     * covered by their parent (order/request lines, cycle & program stages), and
     * high-volume or machine-generated rows (cost allocations, ledger entries,
     * KPI snapshots/narratives, AI usage logs, asset status history).
     */
    protected array $auditedModels = [
        // Master data
        \App\Models\Farm::class,
        \App\Models\Block::class,
        \App\Models\Crop::class,
        \App\Models\Asset::class,
        \App\Models\CropCycle::class,
        \App\Models\SeasonalBudget::class,
        \App\Models\CropProgram::class,

        // Field operations — the day-to-day record of what workers did
        \App\Models\NurseryBatch::class,
        \App\Models\Planting::class,
        \App\Models\DailyActivity::class,
        \App\Models\IrrigationLog::class,
        \App\Models\FertigationLog::class,
        \App\Models\SprayLog::class,

        // Crop monitoring
        \App\Models\GerminationCheck::class,
        \App\Models\PlantPopulationCount::class,
        \App\Models\YieldForecast::class,

        // Labour & workforce
        \App\Models\Worker::class,
        \App\Models\LabourAttendance::class,

        // Land preparation (the step before a crop cycle exists)
        \App\Models\LandPreparation::class,
        \App\Models\LandPreparationTask::class,

        // Projects & task assignment
        \App\Models\Project::class,
        \App\Models\ProjectTask::class,
        \App\Models\TaskAssignment::class,

        // Asset custody (tool check-out / check-in)
        \App\Models\AssetCheckout::class,

        // Harvest & post-harvest
        \App\Models\HarvestBatch::class,
        \App\Models\HarvestByProduct::class,
        \App\Models\WeighScaleReading::class,
        \App\Models\PackhouseLot::class,
        \App\Models\QualityCheck::class,

        // Inventory & procurement
        \App\Models\InventoryItem::class,
        \App\Models\InventoryTransaction::class,
        \App\Models\ProcurementRequest::class,

        // Commercial
        \App\Models\Customer::class,
        \App\Models\SalesOrder::class,
        \App\Models\Dispatch::class,
        \App\Models\Outgrower::class,

        // Stables
        \App\Models\Horse::class,
        \App\Models\Guide::class,
        \App\Models\HorseRide::class,

        // Finance
        \App\Models\Expense::class,

        // Reporting
        \App\Models\AiReport::class,

        // Administration
        \App\Models\User::class,
        \App\Models\ApprovedEmail::class,
        \App\Models\InformationSource::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Demo/dev default to the fake gateway so B2C/C2B work with no
        // Safaricom credentials at all. Set MPESA_DRIVER=daraja once
        // DarajaGateway is filled in (see its docblock) to go live.
        $this->app->bind(MpesaGatewayContract::class, function () {
            if (config('services.mpesa.driver') === 'daraja') {
                return new DarajaGateway(
                    config('services.mpesa.consumer_key'),
                    config('services.mpesa.consumer_secret'),
                    config('services.mpesa.base_url'),
                );
            }

            return new FakeMpesaGateway;
        });
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
