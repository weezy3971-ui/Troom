@extends('layouts.app')
@section('title', 'Search')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Search</h1>
        <p class="page-subtitle">
            @if($q !== '')
                {{ $total }} result{{ $total === 1 ? '' : 's' }} for "{{ $q }}"
            @else
                Find a farm, block, crop, cycle, lot, trace code, or customer
            @endif
        </p>
    </div>
</div>

<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('search') }}" style="display:flex; gap:10px;">
        <input type="text" name="q" value="{{ $q }}" class="form-input" placeholder="Search everything…" autofocus style="flex:1;">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

@if($q !== '' && empty($groups))
    <div class="card">
        <div class="empty-state">
            <div class="icon">🔍</div>
            <h3>No matches for "{{ $q }}"</h3>
            <p>Try a different term — a season name, block, trace code, or customer.</p>
        </div>
    </div>
@else
    @foreach($groups as $groupName => $items)
    <div class="card" style="margin-bottom: 16px;">
        <div class="card-header"><h3 class="card-title">{{ $groupName }} ({{ $items->count() }})</h3></div>
        <div class="table-wrap">
            <table>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td style="width:60%;">
                            <a href="{{ $item['url'] }}" style="color:var(--olive); text-decoration:none; font-weight:600;">{{ $item['label'] }}</a>
                        </td>
                        <td style="color:var(--text-muted);">{{ $item['sub'] }}</td>
                        <td style="text-align:right;"><a href="{{ $item['url'] }}" class="btn btn-ghost btn-sm">Open</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
@endif
@endsection
