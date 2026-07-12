<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\Customer;
use App\Models\Farm;
use App\Models\PackhouseLot;
use App\Support\ModuleAccess;
use Illuminate\Http\Request;

/**
 * Global quick-search: one box that jumps to blocks, crop cycles, farms,
 * crops, customers, or a packhouse lot / traceability code. Result groups are
 * gated by the user's module access.
 */
class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $user = $request->user();
        $groups = [];

        if (strlen($q) >= 1) {
            $like = "%{$q}%";

            // Master data is readable by all roles (spec).
            $groups['Farms'] = Farm::where('name', 'like', $like)->orWhere('location', 'like', $like)
                ->limit(8)->get()->map(fn($f) => ['label' => $f->name, 'sub' => $f->location, 'url' => route('farms.show', $f)]);

            $groups['Blocks'] = Block::with('farm')->where('name', 'like', $like)
                ->limit(8)->get()->map(fn($b) => ['label' => $b->name, 'sub' => $b->farm->name ?? '', 'url' => route('blocks.show', $b)]);

            $groups['Crops'] = Crop::where('name', 'like', $like)->orWhere('variety', 'like', $like)
                ->limit(8)->get()->map(fn($c) => ['label' => trim($c->name . ' ' . ($c->variety ? "({$c->variety})" : '')), 'sub' => $c->crop_type, 'url' => route('crops.show', $c)]);

            $groups['Crop Cycles'] = CropCycle::with('block', 'crop')->where('season_name', 'like', $like)
                ->limit(8)->get()->map(fn($c) => ['label' => $c->season_name, 'sub' => ($c->crop->name ?? '') . ' · ' . ($c->block->name ?? ''), 'url' => route('crop-cycles.show', $c)]);

            if (ModuleAccess::allows($user, 'packhouse')) {
                $groups['Packhouse Lots'] = PackhouseLot::where('lot_number', 'like', $like)->orWhere('traceability_code', 'like', $like)
                    ->limit(8)->get()->map(fn($l) => ['label' => $l->lot_number, 'sub' => $l->traceability_code, 'url' => route('packhouse-lots.show', $l)]);
            }

            if (ModuleAccess::allows($user, 'sales')) {
                $groups['Customers'] = Customer::where('name', 'like', $like)->orWhere('contact', 'like', $like)
                    ->limit(8)->get()->map(fn($c) => ['label' => $c->name, 'sub' => $c->contact, 'url' => route('customers.show', $c)]);
            }

            // Drop empty groups.
            $groups = array_filter($groups, fn($g) => $g->isNotEmpty());
        }

        $total = collect($groups)->sum(fn($g) => $g->count());

        return view('search.index', compact('q', 'groups', 'total'));
    }
}
