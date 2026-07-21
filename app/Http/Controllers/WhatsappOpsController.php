<?php

namespace App\Http\Controllers;

use App\Models\WhatsappMessage;
use App\Services\Whatsapp\TestMessageParser;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WhatsappOpsController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->string('filter', 'pending')->toString();
        $query = WhatsappMessage::with('reviewer')->latest('received_at');

        if ($filter === 'pending') {
            $query->pending();
        } elseif (in_array($filter, ['approved', 'rejected'], true)) {
            $query->where('status', $filter);
        }

        return view('whatsapp-ops.index', [
            'messages' => $query->paginate(30)->withQueryString(),
            'filter' => $filter,
            'pendingCount' => WhatsappMessage::pending()->count(),
        ]);
    }

    public function simulate(Request $request, TestMessageParser $parser)
    {
        abort_unless(config('services.whatsapp.test_mode'), 404);

        $data = $request->validate([
            'sender_name' => 'required|string|max:255',
            'sender_phone' => ['required', 'string', 'max:32', 'regex:/^\+?[0-9 ]{9,20}$/'],
            'body' => 'required|string|max:4000',
        ]);
        $parsed = $parser->parse($data['body']);

        WhatsappMessage::create(array_merge($data, $parsed, [
            'provider' => 'test',
            'external_id' => 'test-'.Str::uuid(),
            'channel_name' => 'Trooms House — Farm Ops (Test)',
            'status' => 'pending_approval',
            'received_at' => now(),
        ]));

        return back()->with('success', 'Test WhatsApp message received and queued for approval.');
    }

    public function approve(Request $request, WhatsappMessage $whatsappMessage)
    {
        abort_unless($whatsappMessage->status === 'pending_approval', 422, 'This message has already been reviewed.');
        $data = $request->validate(['review_note' => 'nullable|string|max:1000']);

        ActivityLogger::as('approved', fn () => $whatsappMessage->update([
            'status' => 'approved',
            'review_note' => $data['review_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]));

        return back()->with('success', 'Interpretation approved and marked ready for ERP mapping.');
    }

    public function reject(Request $request, WhatsappMessage $whatsappMessage)
    {
        abort_unless($whatsappMessage->status === 'pending_approval', 422, 'This message has already been reviewed.');
        $data = $request->validate(['review_note' => 'required|string|max:1000']);

        ActivityLogger::as('rejected', fn () => $whatsappMessage->update([
            'status' => 'rejected',
            'review_note' => $data['review_note'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]));

        return back()->with('success', 'Interpretation rejected and retained for parser improvement.');
    }
}
