<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\CropCycle;
use App\Models\LandPreparation;
use App\Models\LandPreparationTask;
use App\Support\ActivityLogger;
use App\Support\LandPrepProgram;
use App\Support\ModuleAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Land preparation — the step between adding a block and planting into it.
 *
 * Reached from the block: adding a block leads straight here, because the next
 * thing that happens to a new block is that it gets prepared.
 */
class LandPreparationController extends Controller
{
    /**
     * The block's land preparation — the worksheet itself, not a step in front
     * of it. Opening a block that has never been prepared starts the round and
     * lands on it, because "prepare this block" and "here is the checklist" are
     * the same intent and a confirmation screen in between only adds a click.
     */
    public function open(Block $block)
    {
        $prep = $block->landPreparations()->first();

        if (! $prep) {
            if (! ModuleAccess::allows(auth()->user(), 'daily_ops')) {
                return redirect()->route('blocks.show', $block)
                    ->with('error', "{$block->name} hasn't been prepared yet, and you don't have permission to start it.");
            }

            $prep = LandPreparation::startFor($block, auth()->id());
        }

        return redirect()->route('land-preparations.show', $prep);
    }

    /**
     * Start a further round on a block that has already been prepared once —
     * a block gets prepared again before each planting.
     */
    public function store(Request $request, Block $block)
    {
        $prep = LandPreparation::startFor($block, $request->user()?->id);

        return redirect()->route('land-preparations.show', $prep)
            ->with('success', "New preparation round started for {$block->name}.");
    }

    public function show(LandPreparation $landPreparation)
    {
        $landPreparation->load('block.farm', 'tasks', 'cropCycle.crop', 'createdBy', 'expenses');

        // Cycles this preparation could be attributed to: the ones on its block.
        $cycles = CropCycle::with('crop')
            ->where('block_id', $landPreparation->block_id)
            ->latest('planting_date')
            ->get();

        return view('land-preparations.show', [
            'prep' => $landPreparation,
            'cycles' => $cycles,
            'sources' => LandPrepProgram::sources(),
        ]);
    }

    public function update(Request $request, LandPreparation $landPreparation)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(LandPreparation::STATUSES))],
            'started_on' => 'nullable|date',
            'completed_on' => 'nullable|date',
            'crop_cycle_id' => [
                'nullable',
                Rule::exists('crop_cycles', 'id')->where('block_id', $landPreparation->block_id),
            ],
            'notes' => 'nullable|string|max:2000',
        ]);

        // Completing without a date would leave the block's "ready to plant"
        // date unrecorded, which is the one date this whole round exists to fix.
        if ($validated['status'] === 'complete' && empty($validated['completed_on'])) {
            $validated['completed_on'] = now()->toDateString();
        }

        $landPreparation->update($validated);

        return redirect()->route('land-preparations.show', $landPreparation)
            ->with('success', 'Land preparation updated.');
    }

    /**
     * Record that this block doesn't need preparing — already worked, or
     * prepared outside the system. A reason is required, because this is the
     * one way a block gets planted with no preparation on record.
     */
    public function waive(Request $request, LandPreparation $landPreparation)
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:2000',
        ]);

        ActivityLogger::as('marked_not_required', fn () => $landPreparation->update([
            'status' => 'not_required',
            'completed_on' => now()->toDateString(),
            'notes' => $validated['notes'],
        ]));

        return redirect()->route('land-preparations.show', $landPreparation)
            ->with('success', 'Recorded as not required — this cycle can now be activated.');
    }

    /** Tick a step off, mark it not needed, or put it back to pending. */
    public function updateTask(Request $request, LandPreparationTask $task)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(LandPreparationTask::STATUSES))],
            'notes' => 'nullable|string|max:1000',
        ]);

        $task->update($validated + [
            'done_on' => $validated['status'] === 'done' ? now()->toDateString() : null,
        ]);

        $prep = $task->landPreparation;

        // A round that has work recorded against it is under way, whatever it
        // was marked as when it was created.
        if ($prep->status === 'planned' && $validated['status'] !== 'pending') {
            ActivityLogger::as('started', fn () => $prep->update([
                'status' => 'in_progress',
                'started_on' => $prep->started_on ?? now()->toDateString(),
            ]));
        }

        return back()->with('success', "\"{$task->name}\" marked {$task->statusLabel()}.");
    }

    public function destroy(LandPreparation $landPreparation)
    {
        $block = $landPreparation->block;
        $landPreparation->delete();

        return redirect()->route('blocks.show', $block)
            ->with('success', 'Land preparation record deleted.');
    }
}
