@extends('layouts.app')
@section('title', 'Sources')

@php
    // Each category carries its own colour so the single panel stays readable
    // without splitting the page into one long card per category.
    $categoryColors = [
        'crop_cycle' => 'var(--olive)',
        'agronomy' => 'var(--success)',
        'seed_variety' => 'var(--gold)',
        'compliance' => 'var(--terracotta)',
        'soil_lab' => 'var(--info)',
        'market' => 'var(--warning)',
        'other' => 'var(--text-muted)',
    ];
@endphp

@section('content')
<style>
    .src-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 18px; }
    .src-filter {
        font: inherit; font-size: 12px; line-height: 1; cursor: pointer;
        padding: 6px 11px; border-radius: 999px;
        border: 1px solid var(--border-strong); background: transparent; color: var(--text-secondary);
        display: inline-flex; align-items: center; gap: 6px; transition: var(--transition);
    }
    .src-filter:hover { background: var(--bg-secondary); }
    .src-filter[aria-pressed="true"] { background: var(--text-primary); border-color: var(--text-primary); color: var(--bg-card); }
    .src-filter .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--cat, var(--text-muted)); }
    .src-filter[aria-pressed="true"] .dot { box-shadow: 0 0 0 1.5px var(--bg-card); }

    .src-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 12px; }
    .src-tile {
        border: 1px solid var(--border); border-left: 3px solid var(--cat, var(--text-muted));
        border-radius: var(--radius-sm); background: var(--bg-card);
        padding: 13px 14px; display: flex; flex-direction: column; gap: 7px;
        transition: var(--transition);
    }
    .src-tile:hover { background: var(--bg-card-hover); box-shadow: var(--shadow-sm); }
    .src-tile.is-hidden { display: none; }

    .src-name { font-weight: 600; font-size: 14px; color: var(--text-primary); text-decoration: none; }
    .src-name:hover { color: var(--olive); text-decoration: underline; }
    .src-chip {
        display: inline-block; font-size: 10.5px; letter-spacing: .03em; text-transform: uppercase;
        color: var(--cat, var(--text-muted)); border: 1px solid currentColor; border-radius: 999px;
        padding: 1px 7px; white-space: nowrap;
    }
    .src-domain { font-size: 11.5px; color: var(--text-muted); }
    .src-purpose {
        font-size: 12.5px; color: var(--text-secondary); line-height: 1.45; margin: 0;
        display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
    }
    .src-meta { font-size: 11.5px; color: var(--text-muted); }
    .src-meta ul { margin: 7px 0 0; padding-left: 15px; }
    .src-meta li { margin-bottom: 4px; }
    .src-foot { display: flex; gap: 7px; align-items: center; margin-top: auto; padding-top: 4px; }
    .src-foot form { display: inline; }

    .src-empty { font-size: 13px; color: var(--text-secondary); margin: 0; }
    @media (max-width: 520px) { .src-grid { grid-template-columns: 1fr; } }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Sources</h1>
        <p class="page-subtitle">Every website the system takes crop information from, colour-coded by what it's used for</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Sources">
            <p>This list keeps itself up to date. Any website quoted when the system builds a <strong>planting plan</strong> appears here on its own — nothing has to be added by hand.</p>
            <p>Use the <strong>coloured buttons</strong> at the top to show one kind of source at a time. Each source's stripe and tag match its colour.</p>
            <p><strong>Click a source name</strong> (or Open) to check the site in a new tab.</p>
            <p><strong>Delete</strong> a source you don't trust. Its figures stop being published: any crop plan that leaned on it is marked <strong>unverified</strong>, and a plan left with no sources at all is withdrawn from the planner. You're told which plans are affected before you confirm.</p>
            <p>Names and descriptions come from the system itself and aren't edited here — if one is wrong, it's wrong in the code and should be raised with the developer.</p>
        </x-help-panel>
    </div>
</div>

<div class="card" style="margin-bottom: 20px;">
    @if($total === 0)
        <p class="src-empty">No sources are in use. Any website quoted by the planting planner is listed here automatically.</p>
    @else
        <div class="src-toolbar">
            <button type="button" class="src-filter" data-filter="all" aria-pressed="true">All ({{ $total }})</button>
            @foreach($sources as $category => $group)
                <button type="button" class="src-filter" data-filter="{{ $category }}" aria-pressed="false"
                        style="--cat: {{ $categoryColors[$category] ?? 'var(--text-muted)' }};">
                    <span class="dot"></span>{{ $categories[$category] ?? $category }} ({{ $group->count() }})
                </button>
            @endforeach
        </div>

        <div class="src-grid" id="srcGrid">
            @foreach($sources as $category => $group)
                @foreach($group as $source)
                    <article class="src-tile" data-category="{{ $category }}"
                             style="--cat: {{ $categoryColors[$category] ?? 'var(--text-muted)' }};">
                        <div>
                            <a href="{{ $source->url }}" target="_blank" rel="noopener noreferrer" class="src-name">{{ $source->name }}</a>
                            <div class="src-domain mono">{{ $source->domain }}</div>
                        </div>
                        <span class="src-chip">{{ $categories[$category] ?? $category }}</span>

                        @if($source->purpose)
                            <p class="src-purpose" title="{{ $source->purpose }}">{{ $source->purpose }}</p>
                        @endif

                        @php $pages = $source->citedPages(); @endphp
                        <div class="src-meta">
                            @if(count($pages))
                                <details>
                                    <summary style="cursor:pointer;">{{ count($pages) }} page{{ count($pages) === 1 ? '' : 's' }} used</summary>
                                    <ul>
                                        @foreach($pages as $page)
                                            <li>
                                                @if(! empty($page['crop']))<strong>{{ $page['crop'] }}</strong> — @endif
                                                <a href="{{ $page['url'] }}" target="_blank" rel="noopener noreferrer">{{ $page['label'] }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @else
                                No specific page
                            @endif
                        </div>

                        <div class="src-foot">
                            <a href="{{ $source->url }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm">Open</a>
                            <form action="{{ route('information-sources.destroy', $source) }}" method="POST"
                                  data-confirm="Delete {{ $source->name }} ({{ $source->domain }})? @if($source->isCitedInCode())It is cited by {{ count($source->citedPages()) }} page(s) in use. You can put it back from the Deleted section.@endif"
                                  data-confirm-ok="Delete Source">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            @endforeach
        </div>
    @endif
</div>

{{-- Kept collapsed so they don't lengthen the page --}}
@if($removed->isNotEmpty())
<details class="card" style="margin-bottom: 20px;">
    <summary style="cursor:pointer; font-weight:600;">Deleted ({{ $removed->count() }})</summary>
    <p style="font-size: 13px; color: var(--text-secondary); margin: 14px 0 16px;">
        These are not used anywhere and their figures are no longer published. Restoring one puts it, and the plans that relied on it, back.
    </p>
    <div class="src-grid">
        @foreach($removed as $source)
            <article class="src-tile">
                <div>
                    <a href="{{ $source->url }}" target="_blank" rel="noopener noreferrer" class="src-name">{{ $source->name }}</a>
                    <div class="src-domain mono">{{ $source->domain }}</div>
                </div>
                <div class="src-meta">
                    Deleted {{ $source->removed_at?->format('M d, Y') ?? '—' }} by {{ $source->removedBy->name ?? '—' }}
                </div>
                <div class="src-foot">
                    <form action="{{ route('information-sources.restore', $source) }}" method="POST">
                        @csrf @method('PUT')
                        <button type="submit" class="btn btn-secondary btn-sm">Restore</button>
                    </form>
                </div>
            </article>
        @endforeach
    </div>
</details>
@endif

<details class="card">
    <summary style="cursor:pointer; font-weight:600;">Add a Source</summary>
    <p style="font-size: 13px; color: var(--text-secondary); margin: 14px 0 16px;">
        Sources used by the planting planner list themselves. Use this to record one the system doesn't know about yet — one entry per website.
    </p>
    <form action="{{ route('information-sources.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="name">Source name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-input" placeholder="e.g. Greenlife Kenya" required>
                @error('name')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="url">Website address *</label>
                <input type="url" id="url" name="url" value="{{ old('url') }}" class="form-input" placeholder="https://www.greenlife.co.ke/" required>
                @error('url')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="category">Used for *</label>
                <select id="category" name="category" class="form-select" required>
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}" {{ old('category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="purpose">What we use it for</label>
            <textarea id="purpose" name="purpose" class="form-input" rows="2" placeholder="e.g. Spacing, fertiliser rates and harvest timing for the planting planner.">{{ old('purpose') }}</textarea>
            @error('purpose')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary">Add Source</button>
    </form>
</details>

<script>
    // Filter the single panel down to one category at a time, so the list stays
    // one screen rather than growing a card per category.
    document.querySelectorAll('.src-filter').forEach(function (button) {
        button.addEventListener('click', function () {
            var wanted = button.dataset.filter;

            document.querySelectorAll('.src-filter').forEach(function (other) {
                other.setAttribute('aria-pressed', String(other === button));
            });

            document.querySelectorAll('#srcGrid .src-tile').forEach(function (tile) {
                tile.classList.toggle('is-hidden', wanted !== 'all' && tile.dataset.category !== wanted);
            });
        });
    });
</script>
@endsection
