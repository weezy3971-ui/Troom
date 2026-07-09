@props([
    'action' => '',
    'placeholder' => 'Search…',
    'search' => null,
    'total' => null,
    'filters' => [],
])

<form action="{{ $action }}" method="GET" class="search-bar" id="search-bar">
    <div class="search-input-wrap">
        <span class="search-icon">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </span>
        <input
            type="text"
            name="search"
            class="search-input"
            placeholder="{{ $placeholder }}"
            value="{{ $search }}"
            autocomplete="off"
        >
    </div>

    @foreach($filters as $filter)
        <select name="{{ $filter['name'] }}" class="filter-select" onchange="this.form.submit()">
            <option value="">{{ $filter['label'] }}</option>
            @foreach($filter['options'] as $value => $label)
                <option value="{{ $value }}" {{ request($filter['name']) == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    @endforeach

    @if($search || request()->hasAny(collect($filters)->pluck('name')->toArray()))
        <a href="{{ $action }}" class="btn-clear">✕ Clear</a>
        @if($total !== null)
            <span class="search-meta"><strong>{{ $total }}</strong> {{ Str::plural('result', $total) }}</span>
        @endif
    @endif

    <noscript><button type="submit" class="btn btn-ghost btn-sm">Search</button></noscript>
</form>

<script>
(function() {
    const input = document.querySelector('#search-bar .search-input');
    if (!input) return;
    let timer;
    input.addEventListener('input', function() {
        clearTimeout(timer);
        timer = setTimeout(() => this.closest('form').submit(), 400);
    });
})();
</script>
