@php
    // One section, three related views: the catalogue, the plantings of it, and
    // the stage programs that drive them. Kept as a tab strip rather than three
    // sidebar entries so they read as one area of the system.
    $u = auth()->user();
    $ma = \App\Support\ModuleAccess::class;

    $tabs = [
        ['label' => 'Crops', 'route' => 'crops.index', 'active' => request()->routeIs('crops.*')],
        ['label' => 'Crop Cycles', 'route' => 'crop-cycles.index', 'active' => request()->routeIs('crop-cycles.*') && ! request()->routeIs('crop-cycles.planner')],
    ];

    if ($ma::allows($u, 'crop_cycles')) {
        $tabs[] = ['label' => 'Programs', 'route' => 'crop-programs.index', 'active' => request()->routeIs('crop-programs.*')];
    }
@endphp

<style>
    .sec-tabs { display: flex; flex-wrap: wrap; gap: 4px; border-bottom: 2px solid var(--border); margin-bottom: 20px; }
    .sec-tab {
        font-size: 13.5px; font-weight: 600; color: var(--text-secondary); text-decoration: none;
        padding: 9px 14px; border-bottom: 3px solid transparent; margin-bottom: -2px; white-space: nowrap;
    }
    .sec-tab:hover { color: var(--text-primary); }
    .sec-tab.active { color: var(--olive); border-bottom-color: var(--olive); }
</style>

<nav class="sec-tabs" aria-label="Crops and cycles">
    @foreach($tabs as $tab)
        <a href="{{ route($tab['route']) }}" class="sec-tab {{ $tab['active'] ? 'active' : '' }}"
           @if($tab['active']) aria-current="page" @endif>{{ $tab['label'] }}</a>
    @endforeach
</nav>
