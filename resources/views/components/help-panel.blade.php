@props(['title' => 'Guide — how this works'])

{{--
    Contextual in-app guide ("we'll add a tab there with your guide… anytime you
    feel lost"). Collapsible so it stays out of the way until needed. Drop it on
    any page and pass the guidance as the slot content.
--}}
<details class="help-panel" style="margin-bottom: 20px; border: 1px solid var(--border); border-radius: 10px; background: var(--surface, var(--card-bg, transparent)); overflow: hidden;">
    <summary style="cursor: pointer; padding: 12px 18px; font-weight: 600; color: var(--text-primary); list-style: none; display: flex; align-items: center; gap: 8px;">
        <span aria-hidden="true">💡</span> {{ $title }}
        <span style="margin-left: auto; font-weight: 400; font-size: 12px; color: var(--text-muted);">Feeling lost? Tap to open</span>
    </summary>
    <div style="padding: 4px 18px 18px; color: var(--text-secondary, var(--text-muted)); line-height: 1.6;">
        {{ $slot }}
    </div>
</details>
