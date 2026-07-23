@extends('layouts.app')
@section('title', 'Planting Planner')

@section('content')
<x-crumb-nav />
<div class="page-header">
    <div>
        <h1 class="page-title">Planting Planner</h1>
        <p class="page-subtitle">Dated crop schedules</p>
    </div>
</div>

<div class="card">
    <div class="empty-state">
        <div class="icon">🗓</div>
        <h3>No planting plans available</h3>
        <p>Every curated plan has had its backing sources removed, so none can be shown right now.
           Restore a source in <a href="{{ route('information-sources.index') }}">Administration → Sources</a> to bring its plans back.</p>
    </div>
</div>
@endsection
