@props([
    'name',
    'id' => null,
    'value' => '',
    'options' => [],
    'placeholder' => '',
    'required' => false,
])
@php
    $id = $id ?? $name;
@endphp
{{-- Themed combobox: filters as you type, dropdown sits under the input, and
     free text is still allowed (type anything, or pick a suggestion). --}}
<div class="combobox" data-combobox>
    <input
        type="text"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
        class="form-input"
        data-combobox-input
        autocomplete="off"
        role="combobox"
        aria-autocomplete="list"
        aria-expanded="false"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($required) required @endif
        {{ $attributes }}
    >
    <div class="combobox-menu" data-combobox-menu role="listbox" hidden>
        @foreach($options as $opt)
            <div class="combobox-option" role="option" data-value="{{ $opt }}">{{ $opt }}</div>
        @endforeach
    </div>
</div>

@once
<style>
    .combobox { position: relative; }
    .combobox-menu {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        z-index: 60;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        box-shadow: var(--shadow-lg);
        max-height: 240px;
        overflow-y: auto;
        padding: 4px;
    }
    .combobox-menu[hidden] { display: none; }
    .combobox-option {
        padding: 8px 12px;
        font-size: 13px;
        color: var(--text-secondary);
        border-radius: var(--radius-sm);
        cursor: pointer;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        transition: background var(--transition), color var(--transition);
    }
    .combobox-option[hidden] { display: none; }
    .combobox-option:hover,
    .combobox-option.is-active {
        background: var(--olive-bg);
        color: var(--text-primary);
    }
    @media print { .combobox-menu { display: none !important; } }
</style>
<script>
(function () {
    function wrap(el) { return el.closest('[data-combobox]'); }
    function menuOf(input) { return wrap(input).querySelector('[data-combobox-menu]'); }
    function opts(menu) { return Array.prototype.slice.call(menu.querySelectorAll('.combobox-option')); }
    function shown(menu) { return opts(menu).filter(function (o) { return !o.hidden; }); }

    function clearActive(menu) {
        opts(menu).forEach(function (o) { o.classList.remove('is-active'); });
    }

    // Filter options by the typed text; returns whether any match.
    function filter(input) {
        var menu = menuOf(input);
        var q = input.value.trim().toLowerCase();
        var any = false;
        opts(menu).forEach(function (o) {
            var match = o.dataset.value.toLowerCase().indexOf(q) !== -1;
            o.hidden = !match;
            if (match) { any = true; }
        });
        clearActive(menu);
        return any;
    }

    function open(input) {
        var menu = menuOf(input);
        var any = filter(input);
        menu.hidden = !any;
        input.setAttribute('aria-expanded', any ? 'true' : 'false');
    }

    function close(input) {
        var menu = menuOf(input);
        menu.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        clearActive(menu);
    }

    function activeIndex(menu) {
        return shown(menu).findIndex(function (o) { return o.classList.contains('is-active'); });
    }

    function setActive(menu, idx) {
        var vis = shown(menu);
        if (!vis.length) { return; }
        if (idx < 0) { idx = vis.length - 1; }
        if (idx >= vis.length) { idx = 0; }
        clearActive(menu);
        vis[idx].classList.add('is-active');
        vis[idx].scrollIntoView({ block: 'nearest' });
    }

    function choose(input, option) {
        input.value = option.dataset.value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        close(input);
    }

    // Moving focus anywhere closes every other combobox; opens the focused one.
    document.addEventListener('focusin', function (e) {
        var isCombobox = e.target.matches('[data-combobox-input]');
        document.querySelectorAll('[data-combobox-input]').forEach(function (input) {
            if (input !== e.target) { close(input); }
        });
        if (isCombobox) { open(e.target); }
    });

    document.addEventListener('input', function (e) {
        if (e.target.matches('[data-combobox-input]')) { open(e.target); }
    });

    document.addEventListener('keydown', function (e) {
        if (!e.target.matches('[data-combobox-input]')) { return; }
        var input = e.target, menu = menuOf(input);
        if (e.key === 'ArrowDown') {
            if (menu.hidden) { open(input); }
            setActive(menu, activeIndex(menu) + 1);
            e.preventDefault();
        } else if (e.key === 'ArrowUp') {
            if (menu.hidden) { open(input); }
            setActive(menu, activeIndex(menu) - 1);
            e.preventDefault();
        } else if (e.key === 'Enter') {
            var vis = shown(menu), i = activeIndex(menu);
            if (!menu.hidden && i >= 0) {
                choose(input, vis[i]);
                e.preventDefault(); // pick the highlighted option instead of submitting
            }
        } else if (e.key === 'Escape') {
            close(input);
        }
    });

    // mousedown (not click) so selection beats the input's blur.
    document.addEventListener('mousedown', function (e) {
        var option = e.target.closest('.combobox-option');
        if (option) {
            choose(wrap(option).querySelector('[data-combobox-input]'), option);
            e.preventDefault();
        }
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('[data-combobox]')) {
            document.querySelectorAll('[data-combobox-input]').forEach(close);
        }
    });
})();
</script>
@endonce
