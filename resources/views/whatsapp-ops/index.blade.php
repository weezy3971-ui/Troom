@extends('layouts.app')
@section('title', 'WhatsApp Operations')
@section('content')
<style>
    .wa-composer { border-left: 4px solid #25d366; margin-bottom: 20px; }
    .wa-composer-head, .wa-message-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
    .wa-composer .form-group { margin-bottom: 0; }
    .wa-composer-grid { margin-top: 18px; }
    .wa-composer-message { grid-column: 1 / -1; }
    .wa-composer-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 18px; }
    .wa-filters { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
    .wa-filter-count { margin-left: 5px; padding: 1px 7px; }
    .wa-message { margin-bottom: 14px; border-left: 4px solid #25d366; }
    .wa-message.is-issue { border-left-color: #d97706; }
    .wa-sender { display: flex; align-items: baseline; gap: 7px; flex-wrap: wrap; }
    .wa-bubble { background: #f4f8f4; border: 1px solid #edf1ed; border-radius: 12px; padding: 14px 16px; margin: 14px 0; font-size: 15px; line-height: 1.55; }
    .wa-facts { display: flex; gap: 7px; flex-wrap: wrap; margin-bottom: 16px; }
    .wa-review-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; padding-top: 14px; border-top: 1px solid var(--border); }
    .wa-interpretation { display: grid; grid-template-columns: repeat(5, minmax(130px, 1fr)); gap: 12px; align-items: end; padding: 14px; margin-bottom: 14px; background: var(--bg-input); border: 1px solid var(--border); border-radius: 10px; }
    .wa-interpretation .form-group { margin: 0; }
    .wa-interpretation-actions { display: flex; justify-content: flex-end; }
    .wa-review-form { display: flex; align-items: flex-end; gap: 8px; min-width: 0; }
    .wa-review-field { flex: 1; min-width: 0; }
    .wa-review-field .form-label { margin-bottom: 5px; }
    .wa-review-form .btn { white-space: nowrap; min-height: 38px; }
    @media (max-width: 900px) {
        .wa-review-actions { grid-template-columns: 1fr; }
        .wa-interpretation { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 640px) {
        .wa-composer-head, .wa-message-head, .wa-review-form { align-items: stretch; flex-direction: column; }
        .wa-composer-message { grid-column: auto; }
        .wa-interpretation { grid-template-columns: 1fr; }
        .wa-review-form .btn { width: 100%; justify-content: center; }
    }
</style>
<div class="page-header">
    <div>
        <h1 class="page-title">WhatsApp Operations</h1>
        <p class="page-subtitle">Review farm updates before they become ERP records</p>
    </div>
    <span class="badge badge-planned">Safe test mode</span>
</div>

@if(config('services.whatsapp.test_mode'))
<div class="card wa-composer">
    <div class="wa-composer-head">
        <div><h3>Test the farm group</h3><p class="page-subtitle">Send a realistic update in English, Swahili, or both.</p></div>
        <span class="badge badge-neutral">Simulator</span>
    </div>
    <form action="{{ route('whatsapp-ops.simulate') }}" method="POST">@csrf
        <div class="form-grid wa-composer-grid">
            <div class="form-group"><label class="form-label" for="wa_sender_name">Staff name</label><input class="form-input" id="wa_sender_name" name="sender_name" value="{{ old('sender_name', 'Grace') }}" required></div>
            <div class="form-group"><label class="form-label" for="wa_sender_phone">WhatsApp number</label><input class="form-input" id="wa_sender_phone" name="sender_phone" value="{{ old('sender_phone', '+254700000001') }}" required></div>
            <div class="form-group wa-composer-message"><label class="form-label" for="wa_body">Group message</label><textarea class="form-textarea" id="wa_body" name="body" rows="3" required placeholder="Harvested 40kg sukuma wiki from Plot 2">{{ old('body') }}</textarea></div>
        </div>
        <div class="wa-composer-actions"><button class="btn btn-primary">Send test message</button><span class="page-subtitle">Try “Tumepanda mahindi Plot 4” or “Irrigation pipe broken Plot 1”.</span></div>
    </form>
</div>
@endif

<div class="wa-filters">
@foreach(['pending'=>'Needs approval','approved'=>'Ready to post','posted'=>'Posted','rejected'=>'Rejected','all'=>'All'] as $key=>$label)
    <a href="{{ route('whatsapp-ops.index', ['filter'=>$key]) }}" class="btn btn-sm {{ $filter === $key ? 'btn-secondary' : 'btn-ghost' }}">{{ $label }} @if($key==='pending' && $pendingCount)<span class="badge badge-cancelled wa-filter-count">{{ $pendingCount }}</span>@endif</a>
@endforeach
</div>

@forelse($messages as $message)
@php $extracted=$message->extracted_data ?? []; @endphp
<div class="card wa-message {{ $message->intent === 'issue' ? 'is-issue' : '' }}">
    <div class="wa-message-head"><div><div class="wa-sender"><strong>{{ $message->sender_name ?: $message->sender_phone }}</strong><span class="page-subtitle">{{ $message->sender_phone }}</span></div><div class="page-subtitle">{{ $message->channel_name }} · {{ $message->received_at->format('M d, H:i') }}</div></div><span class="badge {{ $message->status==='approved' ? 'badge-completed' : ($message->status==='rejected' ? 'badge-cancelled' : 'badge-active') }}">{{ str_replace('_',' ',ucfirst($message->status)) }}</span></div>
    <div class="wa-bubble">{{ $message->body }}</div>
    <div class="wa-facts"><span class="badge badge-neutral">{{ ucfirst($message->intent) }}</span><span class="badge badge-neutral">{{ strtoupper($message->language) }}</span><span class="badge badge-neutral">{{ round((float)$message->confidence*100) }}% confidence</span>@foreach($extracted as $key=>$value)<span class="badge badge-planned">{{ str_replace('_',' ',$key) }}: {{ is_bool($value) ? ($value?'yes':'no') : $value }}</span>@endforeach</div>
    @if(!$message->posted_at && $message->status !== 'rejected')
    <form class="wa-interpretation" action="{{ route('whatsapp-ops.interpretation.update',$message) }}" method="POST">@csrf @method('PUT')
        <div class="form-group"><label class="form-label">Record type</label><select class="form-select" name="intent">@foreach(['harvest'=>'Harvest','planting'=>'Planting activity','issue'=>'Operational issue','other'=>'General activity'] as $value=>$label)<option value="{{ $value }}" @selected($message->intent===$value)>{{ $label }}</option>@endforeach</select></div>
        <div class="form-group"><label class="form-label">Block</label><select class="form-select" name="block_id"><option value="">Select block</option>@foreach($blocks as $block)<option value="{{ $block->id }}" @selected($message->block_id===$block->id)>{{ $block->name }} · {{ $block->farm->name }}</option>@endforeach</select></div>
        <div class="form-group"><label class="form-label">Crop cycle</label><select class="form-select" name="crop_cycle_id"><option value="">None</option>@foreach($cropCycles as $cycle)<option value="{{ $cycle->id }}" @selected($message->crop_cycle_id===$cycle->id)>{{ $cycle->crop->name }} · {{ $cycle->block->name }} · {{ $cycle->season_name }}</option>@endforeach</select></div>
        <div class="form-group"><label class="form-label">Quantity</label><input class="form-input" type="number" step="0.001" min="0" name="quantity" value="{{ $message->quantity }}" placeholder="40"></div>
        <div class="form-group"><label class="form-label">Unit</label><input class="form-input" name="unit" value="{{ $message->unit }}" placeholder="kg"></div>
        <div class="form-group"><label class="form-label">Activity date</label><input class="form-input" type="date" name="event_date" value="{{ optional($message->event_date)->toDateString() }}" required></div>
        <div class="wa-interpretation-actions"><button class="btn btn-secondary btn-sm">Save interpretation</button></div>
    </form>
    @endif
    @if($message->status==='pending_approval')
    <div class="wa-review-actions">
        <form class="wa-review-form" action="{{ route('whatsapp-ops.approve',$message) }}" method="POST">@csrf<div class="wa-review-field"><label class="form-label">Approval note <span class="page-subtitle">(optional)</span></label><input class="form-input" name="review_note" placeholder="Add context for the ERP record"></div><button class="btn btn-primary btn-sm">Approve</button></form>
        <form class="wa-review-form" action="{{ route('whatsapp-ops.reject',$message) }}" method="POST">@csrf<div class="wa-review-field"><label class="form-label">Correction needed</label><input class="form-input" name="review_note" placeholder="Tell us what was interpreted incorrectly" required></div><button class="btn btn-ghost btn-sm">Reject</button></form>
    </div>
    @elseif($message->status==='approved')
        <div class="wa-review-actions" style="grid-template-columns:1fr auto;align-items:center"><p class="page-subtitle" style="margin:0"><strong>Approved.</strong> Posting creates a permanent ERP record and cannot be repeated.</p><form action="{{ route('whatsapp-ops.post',$message) }}" method="POST" data-confirm="Post this approved message to the ERP?">@csrf<button class="btn btn-primary">Post to ERP</button></form></div>
    @elseif($message->status==='posted')
        <div class="wa-review-actions" style="display:flex;justify-content:space-between;align-items:center"><p class="page-subtitle" style="margin:0"><strong>Posted {{ $message->posted_at->format('M d, H:i') }}</strong> · {{ class_basename($message->posted_record_type) }} #{{ $message->posted_record_id }}</p>@if($message->postedRecord)<a class="btn btn-ghost btn-sm" href="{{ $message->posted_record_type === \App\Models\HarvestBatch::class ? route('harvest-batches.show',$message->posted_record_id) : route('daily-activities.show',$message->posted_record_id) }}">Open ERP record</a>@endif</div>
    @elseif($message->review_note)<p class="page-subtitle"><strong>Review note:</strong> {{ $message->review_note }}</p>@endif
</div>
@empty
<div class="card"><div class="empty-state"><div class="icon">💬</div><h3>No messages here</h3><p>Use the simulator to put the first farm update through the approval flow.</p></div></div>
@endforelse
<div style="margin-top:16px">{{ $messages->links() }}</div>
@endsection
