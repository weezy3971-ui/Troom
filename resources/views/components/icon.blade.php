@props(['name', 'size' => 20, 'solid' => false])
@php
    // Solid-fill variants for the icons used on KPI tiles — read better at a
    // glance and off-angle (field/packhouse screens) than thin line icons.
    $solidIcons = [
        'cycles' => '<path fill-rule="evenodd" d="M12 5V2.4a.4.4 0 0 0-.66-.3L7.2 5.3a.4.4 0 0 0 0 .62l4.14 3.4a.4.4 0 0 0 .66-.31V6.5a5.5 5.5 0 1 1-5.2 3.68 1 1 0 1 0-1.9-.64A7.5 7.5 0 1 0 12 5Z"/>',
        'harvest' => '<path d="M11 2.35 8.5 8H3.4a1 1 0 0 0-.94 1.35l.28.65h18.52l.28-.65A1 1 0 0 0 20.6 8h-5.1L13 2.35a1.1 1.1 0 0 0-2 0Z"/><path d="M3.95 11.5 5.4 19.3A2 2 0 0 0 7.36 21h9.28a2 2 0 0 0 1.96-1.7l1.45-7.8Z"/>',
        'inventory' => '<path fill-rule="evenodd" d="M11.05 2.37a2 2 0 0 1 1.9 0l7 3.8A2 2 0 0 1 21 7.93v8.14a2 2 0 0 1-1.05 1.76l-7 3.8a2 2 0 0 1-1.9 0l-7-3.8A2 2 0 0 1 3 16.07V7.93a2 2 0 0 1 1.05-1.76ZM12 4.4 6.1 7.6 12 10.8l5.9-3.2Z"/>',
        'sales' => '<path fill-rule="evenodd" d="M6 8V6a6 6 0 0 1 12 0v2h2.1a1 1 0 0 1 1 .92l.86 11a1 1 0 0 1-1 1.08H3.04a1 1 0 0 1-1-1.08l.86-11A1 1 0 0 1 3.9 8Zm2 0h8V6a4 4 0 0 0-8 0Z"/>',
        'quality' => '<path fill-rule="evenodd" d="M11.3 2.06a2 2 0 0 1 1.4 0C14.6 3.8 17 5 19 5a1 1 0 0 1 1 1v7c0 5-3.5 7.5-7.7 9a1 1 0 0 1-.6 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.4-1.2 6.3-2.94ZM16.2 10.2a1 1 0 0 0-1.4-1.4L11 12.58l-1.3-1.3a1 1 0 1 0-1.4 1.44l2 2a1 1 0 0 0 1.4 0Z"/>',
        'packhouse' => '<path fill-rule="evenodd" d="M11.13 2.24a2 2 0 0 1 1.74 0l8 3.9A2 2 0 0 1 22 7.93V21a1 1 0 0 1-1 1h-4v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7H3a1 1 0 0 1-1-1V7.93a2 2 0 0 1 1.13-1.8Z"/>',
        'farm' => '<path fill-rule="evenodd" d="M11.14 2.2a1.5 1.5 0 0 1 1.72 0l8 5.6A1 1 0 0 1 21 8.6V21a1 1 0 0 1-1 1h-5v-6a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v6H4a1 1 0 0 1-1-1V8.6a1 1 0 0 1 .42-.82Z"/>',
        'logistics' => '<path fill-rule="evenodd" d="M3 4a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h.6a3 3 0 0 1 5.8 0h4.8a3 3 0 0 1 .3-.62V5a1 1 0 0 0-1-1Zm13 5v6.05a3 3 0 0 1 4.4 1.95H21a1 1 0 0 0 1-1v-4.3a1 1 0 0 0-.2-.6l-2.5-3.3a1 1 0 0 0-.8-.4H16a1 1 0 0 0-1 1zm-9.5 8a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm11 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z"/>',
        'finance' => '<path fill-rule="evenodd" d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2h-5a3 3 0 0 1 0-6h5V8H4a1 1 0 0 1 0-2h16a2 2 0 0 0-2-2Zm13 9a1 1 0 1 0 0 2h5v-2z"/>',
        'settings' => '<path fill-rule="evenodd" d="M13.5 2a1 1 0 0 1 .97.75l.35 1.4c.42.16.82.39 1.18.66l1.38-.42a1 1 0 0 1 1.16.46l1.5 2.6a1 1 0 0 1-.2 1.24l-1.05.96c.03.22.05.45.05.68s-.02.46-.05.68l1.05.96a1 1 0 0 1 .2 1.24l-1.5 2.6a1 1 0 0 1-1.16.46l-1.38-.42c-.36.27-.76.5-1.18.66l-.35 1.4a1 1 0 0 1-.97.75h-3a1 1 0 0 1-.97-.75l-.35-1.4a6.9 6.9 0 0 1-1.18-.66l-1.38.42a1 1 0 0 1-1.16-.46l-1.5-2.6a1 1 0 0 1 .2-1.24l1.05-.96A5.6 5.6 0 0 1 4.7 12c0-.23.02-.46.05-.68l-1.05-.96a1 1 0 0 1-.2-1.24l1.5-2.6a1 1 0 0 1 1.16-.46l1.38.42c.36-.27.76-.5 1.18-.66l.35-1.4A1 1 0 0 1 10.5 2ZM12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z"/>',
    ];

    $icons = [
        'dashboard' => '<rect width="7" height="9" x="3" y="3" rx="1.5"/><rect width="7" height="5" x="3" y="16" rx="1.5"/><rect width="7" height="9" x="14" y="12" rx="1.5"/><rect width="7" height="5" x="14" y="3" rx="1.5"/>',
        'farm' => '<path d="M2 22 L7 13 L10 17 L15 8 L22 22Z"/><circle cx="18" cy="5" r="3"/>',
        'blocks' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/>',
        'crops' => '<path d="M7 20h10"/><path d="M12 20c0-4.5 0-6.5 0-8"/><path d="M12 12C12 8.5 9.5 6.5 5.5 6.5c0 3.5 2.5 5.5 6.5 5.5Z"/><path d="M12 11c0-3 2-4.8 5.5-4.8 0 3-2 4.8-5.5 4.8Z"/>',
        'assets' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'cycles' => '<path d="M21 12a9 9 0 0 0-9-9 9 9 0 0 0-6.7 3L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9 9 0 0 0 6.7-3l2.3-2"/><path d="M16 16h5v5"/>',
        'nursery' => '<path d="M12 22V11"/><path d="M12 11c0-3.3-2.4-5.5-6-5.5 0 3.3 2.4 5.5 6 5.5Z"/><path d="M12 9c0-2.5 1.9-4.2 5-4.2 0 2.5-1.9 4.2-5 4.2Z"/><path d="M6 22h12"/>',
        'operations' => '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M8 11h.01M8 16h.01M12 11h4M12 16h4"/>',
        'irrigation' => '<path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/>',
        'fertigation' => '<path d="M10 2v7.4L4.6 19a2 2 0 0 0 1.7 3h11.4a2 2 0 0 0 1.7-3L14 9.4V2"/><path d="M8.5 2h7"/><path d="M7 16h10"/>',
        'pest' => '<path d="m8 2 1.9 1.9M16 2l-1.9 1.9"/><path d="M12 20v-9"/><path d="M7.5 8a4.5 4.5 0 0 1 9 0v4a4.5 4.5 0 0 1-9 0z"/><path d="M3 13h4M17 13h4M3 8l3 1M18 9l3-1M3 18l3-1M18 17l3 1"/>',
        'labour' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'inventory' => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="M3.3 7 12 12l8.7-5"/><path d="M12 22V12"/>',
        'harvest' => '<path d="M5 11h14l-1.4 9a2 2 0 0 1-2 1.7H8.4a2 2 0 0 1-2-1.7Z"/><path d="M9 11 12 4l3 7"/><path d="M3 11h18"/>',
        'packhouse' => '<path d="M2 20a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8l-7 5V8l-7 5V4a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1Z"/><path d="M6 18h.01M10 18h.01M14 18h.01M18 18h.01"/>',
        'quality' => '<path d="M20 13c0 5-3.5 7.5-7.7 9a1 1 0 0 1-.6 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.2-2.7a1 1 0 0 1 1.6 0C14.5 3.8 17 5 19 5a1 1 0 0 1 1 1Z"/><path d="m9 12 2 2 4-4"/>',
        'sales' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 7h8M8 11h8M8 15h5"/>',
        'logistics' => '<path d="M14 18V6a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h1"/><path d="M14 9h4l4 4v4a1 1 0 0 1-1 1h-1"/><circle cx="7.5" cy="18" r="2"/><circle cx="17.5" cy="18" r="2"/><path d="M9.5 18h6"/>',
        'finance' => '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'home' => '<path d="M3 10.2 12 4l9 6.2"/><path d="M5 9.4V20h14V9.4"/><path d="M9 20v-6h6v6"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'settings' => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
        'notifications' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
        'modules' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'planning' => '<path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="M11 14h2M11 18h2"/>',
        'horse' => '<path d="M8.5 20.5c-3-1.6-4.5-5-4.5-8.5a8 8 0 0 1 16 0c0 3.5-1.5 6.9-4.5 8.5"/><circle cx="6.5" cy="9.5" r=".6" fill="currentColor" stroke="none"/><circle cx="17.5" cy="9.5" r=".6" fill="currentColor" stroke="none"/><circle cx="6.8" cy="13.5" r=".6" fill="currentColor" stroke="none"/><circle cx="17.2" cy="13.5" r=".6" fill="currentColor" stroke="none"/>',
        'chevron' => '<path d="m9 18 6-6-6-6"/>',
        'menu' => '<line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/>',
        'close' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'outgrower' => '<circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M19 8v6"/><path d="M22 11h-6"/>',
        'expenses' => '<path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v2"/><path d="M3 7v10a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1v-8a1 1 0 0 0-1-1H5a2 2 0 0 1-2-2Z"/><path d="M16 13a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z"/>',
        'eye' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off' => '<path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.06"/><path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>',
        // Trooms House Farms & Equestrian mark — the estate's house-and-door
        // logo, redrawn on the 24x24 grid so it inherits the same stroke weight
        // and round caps as every other icon here instead of sitting in the UI
        // as a foreign raster.
        'thf' => '<path d="M1.9 10.3 12 1.3l10.1 9"/><path d="M4.5 10.7v8.8a2.4 2.4 0 0 0 2.4 2.4h10.2a2.4 2.4 0 0 0 2.4-2.4v-8.8"/><path d="M10.1 21.9V13a1.45 1.45 0 0 1 2.9 0v8.9"/>',
    ];

    $useSolid = $solid && isset($solidIcons[$name]);
    $paths = $useSolid ? $solidIcons[$name] : ($icons[$name] ?? '');
@endphp
@if($useSolid)
<svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="currentColor" {{ $attributes }}>
    {!! $paths !!}
</svg>
@else
<svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" {{ $attributes }}>
    {!! $paths !!}
</svg>
@endif
