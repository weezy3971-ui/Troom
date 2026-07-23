<?php

namespace App\Console\Commands;

use App\Services\ScheduleReminderService;
use Illuminate\Console\Command;

/**
 * Daily driver for the reminder engine. Scheduled in routes/console.php.
 */
class SendScheduleReminders extends Command
{
    protected $signature = 'horticulture:send-reminders
        {--date= : Run as if today were this date (YYYY-MM-DD), for backfill and testing}
        {--dry-run : Report what would be raised without writing or sending}';

    protected $description = 'Raise tasks and send reminders for crop cycle schedule points that have come due';

    public function handle(ScheduleReminderService $reminders): int
    {
        $on = $this->option('date') ? \Illuminate\Support\Carbon::parse($this->option('date')) : now();

        if ($this->option('dry-run')) {
            return $this->reportDryRun($on);
        }

        $result = $reminders->run($on);

        $this->info(sprintf(
            'Checked %d active cycle(s) as of %s: %d task(s) raised, %d escalated.',
            $result['cycles'],
            $on->toDateString(),
            $result['created'],
            $result['escalated'],
        ));

        return self::SUCCESS;
    }

    private function reportDryRun(\Illuminate\Support\Carbon $on): int
    {
        $cycles = \App\Models\CropCycle::query()
            ->where('status', 'active')
            ->whereNotNull('crop_cycle_template_id')
            ->whereNotNull('planting_date')
            ->with(['template.schedulePoints', 'activities', 'block'])
            ->get();

        $rows = [];

        foreach ($cycles as $cycle) {
            foreach ($cycle->resolvedSchedule() as $row) {
                if (in_array($row['status'], ['due', 'overdue'], true)) {
                    $rows[] = [
                        $cycle->block?->name ?? '—',
                        $cycle->season_name,
                        $row['due_date']?->toDateString() ?? '—',
                        $row['status'],
                        $row['point']->description(),
                    ];
                }
            }
        }

        if (! $rows) {
            $this->info("Nothing due as of {$on->toDateString()}.");

            return self::SUCCESS;
        }

        $this->table(['Block', 'Season', 'Due', 'Status', 'Task'], $rows);

        return self::SUCCESS;
    }
}
