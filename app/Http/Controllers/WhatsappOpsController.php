<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\CropCycle;
use App\Models\DailyActivity;
use App\Models\HarvestBatch;
use App\Models\WhatsappMessage;
use App\Services\Whatsapp\TestMessageParser;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WhatsappOpsController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->string('filter', 'pending')->toString();
        $query = WhatsappMessage::with('reviewer', 'block.farm', 'cropCycle.crop', 'postedRecord')->latest('received_at');

        if ($filter === 'pending') {
            $query->pending();
        } elseif (in_array($filter, ['approved', 'rejected', 'posted'], true)) {
            $query->where('status', $filter);
        }

        return view('whatsapp-ops.index', [
            'messages' => $query->paginate(30)->withQueryString(),
            'filter' => $filter,
            'pendingCount' => WhatsappMessage::pending()->count(),
            'blocks' => Block::with('farm')->orderBy('name')->get(),
            'cropCycles' => CropCycle::with('block', 'crop')->whereIn('status', ['active', 'planned'])->orderBy('season_name')->get(),
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

        $extracted = $parsed['extracted_data'];
        $areaText = Str::lower((string) ($extracted['area_text'] ?? ''));
        $matchedBlock = $areaText === '' ? null : Block::query()->get()->first(
            fn (Block $block) => Str::contains(Str::lower($block->name), $areaText)
        );
        $matchingCycles = CropCycle::with('crop')
            ->where('status', 'active')
            ->when($matchedBlock, fn ($query) => $query->where('block_id', $matchedBlock->id))
            ->get();
        $matchedCycle = $matchingCycles->first(
            fn (CropCycle $cycle) => Str::contains(Str::lower($data['body']), Str::lower($cycle->crop->name))
        ) ?? ($matchingCycles->count() === 1 ? $matchingCycles->first() : null);

        WhatsappMessage::create(array_merge($data, $parsed, [
            'provider' => 'test',
            'external_id' => 'test-'.Str::uuid(),
            'channel_name' => 'Trooms House — Farm Ops (Test)',
            'status' => 'pending_approval',
            'received_at' => now(),
            'quantity' => $extracted['quantity'] ?? null,
            'unit' => $extracted['unit'] ?? null,
            'event_date' => now()->toDateString(),
            'block_id' => $matchedBlock?->id,
            'crop_cycle_id' => $matchedCycle?->id,
        ]));

        return back()->with('success', 'Test WhatsApp message received and queued for approval.');
    }

    public function updateInterpretation(Request $request, WhatsappMessage $whatsappMessage)
    {
        abort_if($whatsappMessage->posted_at, 422, 'Posted records can no longer be edited.');

        $data = $request->validate([
            'intent' => ['required', Rule::in(['harvest', 'planting', 'issue', 'other'])],
            'block_id' => 'nullable|exists:blocks,id',
            'crop_cycle_id' => 'nullable|exists:crop_cycles,id',
            'quantity' => 'nullable|numeric|min:0.001',
            'unit' => 'nullable|string|max:24',
            'event_date' => 'required|date',
        ]);

        if (! empty($data['crop_cycle_id'])) {
            $cycle = CropCycle::findOrFail($data['crop_cycle_id']);
            if (! empty($data['block_id']) && $cycle->block_id !== (int) $data['block_id']) {
                throw ValidationException::withMessages(['crop_cycle_id' => 'The crop cycle must belong to the selected block.']);
            }
            $data['block_id'] = $cycle->block_id;
        }

        $whatsappMessage->update($data);

        return back()->with('success', 'Interpretation updated.');
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

    public function post(Request $request, WhatsappMessage $whatsappMessage)
    {
        $posted = DB::transaction(function () use ($request, $whatsappMessage) {
            $message = WhatsappMessage::lockForUpdate()->findOrFail($whatsappMessage->id);
            abort_unless($message->status === 'approved', 422, 'Approve the interpretation before posting.');

            if ($message->posted_at) {
                return $message->postedRecord;
            }

            if (! $message->block_id) {
                throw ValidationException::withMessages(['block_id' => 'Select a block before posting.']);
            }

            if ($message->intent === 'harvest') {
                if (! $message->crop_cycle_id || ! $message->quantity || ! in_array(Str::lower((string) $message->unit), ['kg', 'kgs', 'kilogram', 'kilograms'], true)) {
                    throw ValidationException::withMessages(['quantity' => 'Harvest posting requires a crop cycle and a quantity in kilograms.']);
                }
                $cycle = CropCycle::with('sprayLogs')->findOrFail($message->crop_cycle_id);
                if ($cycle->hasActivePreHarvestInterval()) {
                    throw ValidationException::withMessages(['crop_cycle_id' => 'Harvest is blocked by an active pre-harvest interval.']);
                }
                $record = HarvestBatch::create([
                    'crop_cycle_id' => $cycle->id,
                    'block_id' => $message->block_id,
                    'harvest_date' => $message->event_date,
                    'quantity_kg' => $message->quantity,
                    'rejects_kg' => 0,
                    'harvested_by' => $request->user()->id,
                ]);
            } else {
                $record = DailyActivity::create([
                    'block_id' => $message->block_id,
                    'crop_cycle_id' => $message->crop_cycle_id,
                    'activity_type' => $message->intent === 'planting' ? 'planting' : 'general',
                    'activity_date' => $message->event_date,
                    'description' => ($message->intent === 'issue' ? '[WhatsApp issue] ' : '[WhatsApp] ').$message->body,
                    'logged_by' => $request->user()->id,
                    'client_uuid' => Str::uuid(),
                ]);
            }

            $message->update([
                'status' => 'posted',
                'posted_record_type' => $record::class,
                'posted_record_id' => $record->getKey(),
                'posted_at' => now(),
            ]);

            return $record;
        });

        ActivityLogger::log('posted_to_erp', $whatsappMessage, 'Posted WhatsApp message to '.class_basename($posted));

        return back()->with('success', 'Message posted to the ERP successfully.');
    }
}
