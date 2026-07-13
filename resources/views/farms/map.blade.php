@extends('layouts.app')
@section('title', 'Farm Map')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Farm Map</h1>
        <p class="page-subtitle">Farms plotted by location — green markers have an active crop cycle</p>
    </div>
    <a href="{{ route('farms.index') }}" class="btn btn-secondary">List View</a>
</div>

@if($farms->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="icon">🗺️</div>
            <h3>No mapped farms</h3>
            <p>Add coordinates (latitude &amp; longitude) to your farms to see them here.</p>
        </div>
    </div>
@else
    {{-- Leaflet map. Tiles are served from the public OpenStreetMap CDN
         (the one external dependency in this feature). --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <div class="card" style="padding: 8px;">
        <div id="farm-map" style="height: 560px; width: 100%; border-radius: var(--radius-sm);"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var farms = @json($farms);
        var map = L.map('farm-map');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var bounds = [];
        farms.forEach(function (f) {
            var active = f.active_cycles > 0;
            var color = active ? '#3C8048' : '#5C6A61';
            var marker = L.circleMarker([f.lat, f.lng], {
                radius: 10, color: '#fff', weight: 2, fillColor: color, fillOpacity: 0.9
            }).addTo(map);
            marker.bindPopup(
                '<strong>' + f.name + '</strong><br>' +
                (f.location ? f.location + '<br>' : '') +
                f.blocks + ' block(s) · ' + f.assets + ' asset(s)<br>' +
                '<span style="color:' + color + ';font-weight:600;">' + f.active_cycles + ' active cycle(s)</span><br>' +
                '<a href="' + f.url + '">Open farm →</a>'
            );
            bounds.push([f.lat, f.lng]);
        });

        if (bounds.length === 1) {
            map.setView(bounds[0], 12);
        } else if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [40, 40] });
        } else {
            map.setView([0.02, 37.0], 6); // Kenya fallback
        }
    });
    </script>
@endif
@endsection
