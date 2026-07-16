@props(['title' => 'Quick Tips'])

{{--
    Contextual in-app help — click the "?" button to open a floating tip panel.
    Drop it on any page and pass the guidance as the slot content.
--}}
<div class="help-tip-wrapper" style="position: relative; display: inline-block;">
    <button type="button" onclick="this.nextElementSibling.toggleAttribute('open')" class="btn btn-ghost btn-sm" style="border-radius: 50%; width: 34px; height: 34px; padding: 0; font-size: 16px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--border); color: var(--text-muted);" title="{{ $title }}">?</button>
    <div class="help-tip-panel" style="display: none; position: fixed; top: 0; right: 0; bottom: 0; width: min(420px, 90vw); background: var(--card-bg, #fff); border-left: 1px solid var(--border); box-shadow: -4px 0 24px rgba(0,0,0,.12); z-index: 999; overflow-y: auto; padding: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <h3 style="margin: 0; font-family: var(--font-display); font-size: 16px; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                <span>💡</span> {{ $title }}
            </h3>
            <button type="button" onclick="this.closest('.help-tip-panel').removeAttribute('open')" class="btn btn-ghost btn-sm" style="border-radius: 50%; width: 30px; height: 30px; padding: 0; font-size: 18px; line-height: 1; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">✕</button>
        </div>
        <div style="color: var(--text-secondary, var(--text-muted)); line-height: 1.7; font-size: 14px;">
            {{ $slot }}
        </div>
    </div>
</div>

<style>
    .help-tip-panel[open] { display: block !important; animation: helpSlideIn .2s ease; }
    @keyframes helpSlideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
</style>
