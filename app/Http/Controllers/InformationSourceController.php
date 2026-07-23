<?php

namespace App\Http\Controllers;

use App\Models\InformationSource;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Administration → Sources.
 *
 * The register of external websites the agronomy in this system is drawn from —
 * the references an agronomist can cite when building a crop cycle template.
 * The admin's controls are deliberately narrow: open a source to check it, or
 * remove it so it stops being offered.
 */
class InformationSourceController extends Controller
{
    public function index()
    {
        $sources = InformationSource::inUse()
            ->orderBy('name')
            ->get()
            ->groupBy('category')
            ->sortBy(fn ($group, $category) => array_search($category, array_keys(InformationSource::CATEGORIES), true));

        return view('information-sources.index', [
            'sources' => $sources,
            'removed' => InformationSource::removed()->with('removedBy')->orderBy('name')->get(),
            'total' => $sources->flatten()->count(),
            'categories' => InformationSource::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:2048',
            'category' => ['required', Rule::in(array_keys(InformationSource::CATEGORIES))],
            'purpose' => 'nullable|string|max:1000',
        ]);

        $domain = InformationSource::domainFrom($validated['url']);

        if ($domain === '') {
            return back()->withInput()->withErrors(['url' => 'That URL has no website address in it.']);
        }

        $existing = InformationSource::firstWhere('domain', $domain);

        // Re-adding something previously removed puts it back rather than
        // failing on the unique domain with an error nobody can act on.
        if ($existing) {
            if (! $existing->isRemoved()) {
                return back()->withInput()->withErrors(['url' => "{$domain} is already listed."]);
            }

            $existing->update($validated + ['status' => 'active', 'removed_at' => null, 'removed_by' => null]);

            return redirect()->route('information-sources.index')
                ->with('success', "{$existing->name} is back in use.");
        }

        InformationSource::create($validated + [
            'domain' => $domain,
            'status' => 'active',
            'added_by' => auth()->id(),
        ]);

        return redirect()->route('information-sources.index')
            ->with('success', "{$validated['name']} added to the source list.");
    }

    /**
     * Take a source out of use. It is kept as a removed row rather than erased,
     * so the audit trail keeps the record of what was once cited and by whom.
     */
    public function destroy(InformationSource $informationSource)
    {
        $name = $informationSource->name;

        ActivityLogger::as('deleted', fn () => $informationSource->update([
            'status' => 'removed',
            'removed_at' => now(),
            'removed_by' => auth()->id(),
        ]));

        return redirect()->route('information-sources.index')
            ->with('success', "{$name} deleted.");
    }

    /** Put a removed source back into use. */
    public function restore(InformationSource $informationSource)
    {
        ActivityLogger::as('restored', fn () => $informationSource->update([
            'status' => 'active',
            'removed_at' => null,
            'removed_by' => null,
        ]));

        return redirect()->route('information-sources.index')
            ->with('success', "{$informationSource->name} restored.");
    }

}
