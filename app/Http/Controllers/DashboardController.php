<?php

namespace App\Http\Controllers;

use App\Models\ApprovedEmail;
use App\Models\CropCycle;
use App\Models\Dispatch;
use App\Models\HarvestBatch;
use App\Models\InventoryItem;
use App\Models\PackhouseLot;
use App\Models\SalesOrder;
use App\Services\AlertService;
use App\Support\ModuleAccess;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(AlertService $alerts)
    {
        $user = auth()->user();

        // Role-tailored "focus" widgets: each user sees only the counts that
        // matter to what they do, gated by the same module access as the sidebar.
        $focus = [];

        $allow = fn(string $module) => ModuleAccess::allows($user, $module);

        if ($allow('crop_cycles') || $allow('master_data')) {
            $focus[] = [
                'label' => 'Active crop cycles',
                'icon' => 'cycles',
                'value' => CropCycle::where('status', 'active')->count(),
                'sub' => 'in the field now',
                'url' => route('crop-cycles.index', ['status' => 'active']),
                'tone' => 'success',
            ];

            $approaching = CropCycle::where('status', 'active')
                ->whereNotNull('expected_harvest_date')
                ->whereBetween('expected_harvest_date', [Carbon::today(), Carbon::today()->addDays(14)])
                ->count();
            $focus[] = [
                'label' => 'Harvest approaching',
                'icon' => 'harvest',
                'value' => $approaching,
                'sub' => 'due within 14 days',
                'url' => route('crop-cycles.index'),
                'tone' => $approaching > 0 ? 'warning' : 'muted',
            ];
        }

        if ($allow('harvest')) {
            $thisWeek = HarvestBatch::whereBetween('harvest_date', [Carbon::today()->subDays(7), Carbon::today()])->count();
            $focus[] = [
                'label' => 'Harvested this week',
                'icon' => 'harvest',
                'value' => $thisWeek,
                'sub' => 'batches in last 7 days',
                'url' => route('harvest-batches.index'),
                'tone' => 'success',
            ];
        }

        if ($allow('packhouse')) {
            $unpacked = HarvestBatch::doesntHave('packhouseLots')->count();
            $focus[] = [
                'label' => 'Awaiting packing',
                'icon' => 'packhouse',
                'value' => $unpacked,
                'sub' => 'harvest batches not yet packed',
                'url' => route('harvest-batches.index'),
                'tone' => $unpacked > 0 ? 'warning' : 'muted',
            ];
        }

        if ($allow('quality')) {
            $pendingQc = PackhouseLot::doesntHave('qualityChecks')->count();
            $focus[] = [
                'label' => 'Lots pending QC',
                'icon' => 'quality',
                'value' => $pendingQc,
                'sub' => 'not yet quality-checked',
                'url' => route('quality-checks.index'),
                'tone' => $pendingQc > 0 ? 'warning' : 'muted',
            ];
        }

        if ($allow('inventory')) {
            $lowStock = InventoryItem::with('transactions')->get()->filter->isLowStock()->count();
            $focus[] = [
                'label' => 'Low stock items',
                'icon' => 'inventory',
                'value' => $lowStock,
                'sub' => 'below reorder level',
                'url' => route('inventory-items.index'),
                'tone' => $lowStock > 0 ? 'danger' : 'muted',
            ];
        }

        if ($allow('sales')) {
            $atRisk = SalesOrder::with('lines')->get()->filter->isAtRisk()->count();
            $focus[] = [
                'label' => 'Orders at risk',
                'icon' => 'sales',
                'value' => $atRisk,
                'sub' => 'under-allocated near delivery',
                'url' => route('sales-orders.index'),
                'tone' => $atRisk > 0 ? 'danger' : 'muted',
            ];
        }

        if ($allow('logistics')) {
            $scheduled = Dispatch::where('status', 'scheduled')->count();
            $focus[] = [
                'label' => 'Dispatches scheduled',
                'icon' => 'logistics',
                'value' => $scheduled,
                'sub' => 'awaiting departure',
                'url' => route('dispatches.index'),
                'tone' => $scheduled > 0 ? 'info' : 'muted',
            ];
        }

        if ($allow('finance')) {
            $overBudget = CropCycle::with('seasonalBudget', 'costAllocations')->get()->filter->isBudgetExceeded()->count();
            $focus[] = [
                'label' => 'Cycles over budget',
                'icon' => 'finance',
                'value' => $overBudget,
                'sub' => 'actual cost above budget',
                'url' => route('finance.index'),
                'tone' => $overBudget > 0 ? 'danger' : 'muted',
            ];
        }

        if ($allow('admin')) {
            $pending = ApprovedEmail::whereNull('registered_at')->count();
            $focus[] = [
                'label' => 'Pending registrations',
                'icon' => 'settings',
                'value' => $pending,
                'sub' => 'approved, not yet signed up',
                'url' => route('users.index'),
                'tone' => $pending > 0 ? 'info' : 'muted',
            ];
        }

        // Alerts relevant to this user's modules only.
        $moduleForAlert = [
            'Finance' => 'finance', 'Inventory' => 'inventory', 'Sales' => 'sales', 'Machinery' => 'irrigation',
        ];
        $myAlerts = collect($alerts->collect())->filter(function ($a) use ($allow, $moduleForAlert) {
            $module = $moduleForAlert[$a['module']] ?? null;
            return $module === null || $allow($module) || $allow('analytics');
        })->values()->all();

        return view('dashboard', compact('focus', 'myAlerts'));
    }
}
