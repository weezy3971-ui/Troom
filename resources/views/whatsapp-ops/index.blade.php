@extends('layouts.app')
@section('title', 'WhatsApp Operations')
@section('content')
<div class="page-header">
    <div><h1 class="page-title">WhatsApp Operations</h1><p class="page-subtitle">Approval-first pilot inbox · approved messages do not post to ERP yet</p></div>
    <span class="badge badge-planned">Test mode</span>
</div>

@if(config('services.whatsapp.test_mode'))
<div class="card" style="margin-bottom:18px;border-left:4px solid #25D366">
    <h3>Send a test group message</h3>
    <p class="page-subtitle">Try natural English, Swahili, or a mix of both.</p>
    <form action="{{ route('whatsapp-ops.simulate') }}" method="POST">@csrf
        <div class="form-grid" style="grid-template-columns:1fr 1fr">
            <div class="form-group"><label>Staff name</label><input name="sender_name" value="{{ old('sender_name', 'Grace') }}" required></div>
            <div class="form-group"><label>Phone</label><input name="sender_phone" value="{{ old('sender_phone', '+254700000001') }}" required></div>
            <div class="form-group" style="grid-column:1/-1"><label>Message</label><textarea name="body" rows="3" required placeholder="Harvested 40kg sukuma wiki from Plot 2">{{ old('body') }}</textarea></div>
        </div>
        <div class="actions"><button class="btn btn-primary">Simulate WhatsApp message</button><span class="page-subtitle">Try “Tumepanda mahindi Plot 4” or “Irrigation pipe broken Plot 1”.</span></div>
    </form>
</div>
@endif

<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
@foreach(['pending'=>'Needs approval','approved'=>'Approved','rejected'=>'Rejected','all'=>'All'] as $key=>$label)
    <a href="{{ route('whatsapp-ops.index', ['filter'=>$key]) }}" class="btn btn-sm {{ $filter === $key ? 'btn-secondary' : 'btn-ghost' }}">{{ $label }} @if($key==='pending' && $pendingCount)<span class="badge badge-cancelled">{{ $pendingCount }}</span>@endif</a>
@endforeach
</div>

@forelse($messages as $message)
@php $extracted=$message->extracted_data ?? []; @endphp
<div class="card" style="margin-bottom:14px;border-left:4px solid {{ $message->intent === 'issue' ? '#d97706' : '#25D366' }}">
    <div style="display:flex;justify-content:space-between;gap:16px"><div><strong>{{ $message->sender_name ?: $message->sender_phone }}</strong> <span class="page-subtitle">{{ $message->sender_phone }}</span><div class="page-subtitle">{{ $message->channel_name }} · {{ $message->received_at->format('M d, H:i') }}</div></div><span class="badge {{ $message->status==='approved' ? 'badge-completed' : ($message->status==='rejected' ? 'badge-cancelled' : 'badge-active') }}">{{ str_replace('_',' ',ucfirst($message->status)) }}</span></div>
    <div style="background:#f7faf7;border-radius:10px;padding:14px 16px;margin:14px 0;font-size:16px">{{ $message->body }}</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px"><span class="badge badge-neutral">{{ ucfirst($message->intent) }}</span><span class="badge badge-neutral">{{ strtoupper($message->language) }}</span><span class="badge badge-neutral">{{ round((float)$message->confidence*100) }}% confidence</span>@foreach($extracted as $key=>$value)<span class="badge badge-planned">{{ str_replace('_',' ',$key) }}: {{ is_bool($value) ? ($value?'yes':'no') : $value }}</span>@endforeach</div>
    @if($message->status==='pending_approval')
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <form action="{{ route('whatsapp-ops.approve',$message) }}" method="POST">@csrf<input name="review_note" placeholder="Optional approval note" style="margin-bottom:8px"><button class="btn btn-primary btn-sm">Approve interpretation</button></form>
        <form action="{{ route('whatsapp-ops.reject',$message) }}" method="POST">@csrf<input name="review_note" placeholder="Why is this interpretation wrong?" required style="margin-bottom:8px"><button class="btn btn-ghost btn-sm">Reject & retain for learning</button></form>
    </div>
    @elseif($message->review_note)<p class="page-subtitle"><strong>Review note:</strong> {{ $message->review_note }}</p>@endif
</div>
@empty
<div class="card"><div class="empty-state"><div class="icon">💬</div><h3>No messages here</h3><p>Use the simulator to put the first farm update through the approval flow.</p></div></div>
@endforelse
<div style="margin-top:16px">{{ $messages->links() }}</div>
@endsection
