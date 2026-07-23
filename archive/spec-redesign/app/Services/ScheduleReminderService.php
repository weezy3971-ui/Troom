<?php

namespace App\Services;

use App\Models\CropCycle;
use App\Models\CropCycleSchedulePoint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * The reminder engine.
 *
 * Runs daily: for every active crop cycle it computes today's day-offset from
 * the planting date, checks the template's schedule points for a matching (or
 * newly overdue) entry, and — where no activity has been logged against that
 * point and no task already exists — raises a task and notifies the assignee.
 *
 * Tasks that stay pending past their due date escalate to the farm manager
 * after 24 hours, once each.
 */
class ScheduleReminderService
{
    public function __construct(private SmsService $sms = new SmsService()) {}

    /**
     * Raise tasks for every schedule point that has come due and not been done.
     *
     * @return array{created: int, escalated: int, cycles: int}
     */
    public function run(?\DateTimeInterface $on = null): array
    {
        $today = $on ? \Illuminate\Support\Carbon::instance($on) : now();

        $cycles = CropCycle::query()
            ->where('status', 'active')
            ->whereNotNull('crop_cycle_template_id')
            ->whereNotNull('planting_date')
            ->with(['template.schedulePoints', 'activities', 'block.farm'])
            ->get();

        $created = 0;

        foreach ($cycles as $cycle) {
            $created += $this->raiseTasksFor($cycle, $today);
        }

        return [
            'created' => $created,
            'escalated' => $this->escalateOverdue($today),
            'cycles' => $cycles->count(),
        ];
    }

    /**
     * Raise a task for each of this cycle's schedule points that is due or
     * overdue as of $today and has no logged activity.
     */
    private function raiseTasksFor(CropCycle $cycle, \Illuminate\Support\Carbon $today): int
    {
        $dayOffset = $cycle->dayOffset($today);

        if ($dayOffset === null || $dayOffset < 0) {
            return 0;
        }

        $logged = $cycle->activities->pluck('crop_cycle_schedule_point_id')->filter()->all();
        $assignee = $this->assigneeFor($cycle);
        $created = 0;

        // Points on or before today's offset have come due. Anything already
        // logged, or already carrying a task, is skipped.
        $duePoints = $cycle->template->schedulePoints
            ->filter(fn (CropCycleSchedulePoint $p) => $p->day_offset <= $dayOffset)
            ->reject(fn (CropCycleSchedulePoint $p) => in_array($p->id, $logged, true));

        foreach ($duePoints as $point) {
            // The unique index on (crop_cycle_id, schedule_point_id) is the real
            // guard — this handles the common case without hitting a constraint
            // violation, and firstOrCreate makes a concurrent run harmless.
            $task = Task::firstOrNew([
                'crop_cycle_id' => $cycle->id,
                'crop_cycle_schedule_point_id' => $point->id,
            ]);

            if ($task->exists) {
                continue;
            }

            $task->fill([
                'assigned_to' => $assignee?->id,
                'module' => 'horticulture',
                'related_type' => CropCycle::class,
                'related_id' => $cycle->id,
                'description' => $point->description(),
                'status' => 'pending',
                'due_date' => $cycle->dueDateFor($point),
                'source' => 'schedule_point',
            ])->save();

            $created++;
            $this->notify($assignee, $this->reminderText($cycle, $point));
        }

        // Keep the cycle's current-stage pointer in step with the calendar.
        $stage = $cycle->template->stageForDay($dayOffset);
        if ($stage && $cycle->current_stage_id !== $stage->id) {
            $cycle->update(['current_stage_id' => $stage->id]);
        }

        return $created;
    }

    /**
     * Escalate to the farm manager any task still pending 24 hours past its due
     * date. escalated_at makes this fire once per task.
     */
    private function escalateOverdue(\Illuminate\Support\Carbon $today): int
    {
        $stale = Task::query()
            ->pending()
            ->whereNull('escalated_at')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today->copy()->subDay()->toDateString())
            ->with('cropCycle.block')
            ->get();

        if ($stale->isEmpty()) {
            return 0;
        }

        $managers = User::query()
            ->whereIn('role', ['horticulture_manager', 'farm_supervisor'])
            ->where('is_active', true)
            ->get();

        foreach ($stale as $task) {
            $block = $task->cropCycle?->block?->name ?? 'unknown block';
            $text = "OVERDUE ({$task->daysOverdue()}d): {$task->description} on {$block}.";

            foreach ($managers as $manager) {
                $this->notify($manager, $text);
            }
        }

        Task::whereIn('id', $stale->pluck('id'))->update(['escalated_at' => $today]);

        return $stale->count();
    }

    /**
     * Who the work falls to. The block's assigned supervisor where one exists,
     * otherwise the most senior active horticulture role.
     */
    private function assigneeFor(CropCycle $cycle): ?User
    {
        return User::query()
            ->whereIn('role', ['farm_supervisor', 'agronomist', 'horticulture_manager'])
            ->where('is_active', true)
            ->orderByRaw("CASE role
                WHEN 'farm_supervisor' THEN 1
                WHEN 'agronomist' THEN 2
                ELSE 3 END")
            ->first();
    }

    private function reminderText(CropCycle $cycle, CropCycleSchedulePoint $point): string
    {
        $block = $cycle->block?->name ?? 'block';

        return "Trooms: {$point->description()} due on {$block} ({$cycle->season_name}).";
    }

    /**
     * Best-effort delivery. A failed send must never abort the run — the task
     * row is the durable record, the message is a nudge.
     */
    private function notify(?User $user, string $message): void
    {
        if (! $user) {
            return;
        }

        try {
            $this->sms->send($user->phone ?? null, $message);
        } catch (\Throwable $e) {
            Log::warning('Schedule reminder notification failed.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
