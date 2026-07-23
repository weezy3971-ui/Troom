<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\CropCycleTemplate;
use App\Models\Farm;
use App\Models\Task;
use App\Models\User;
use App\Services\ScheduleReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleReminderTest extends TestCase
{
    use RefreshDatabase;

    private function supervisor(): User
    {
        return User::factory()->create(['role' => 'farm_supervisor', 'is_active' => true]);
    }

    /**
     * A 90-day template with a spray on day 60 and a harvest check on day 85,
     * run by a block that planted $daysAgo days ago.
     */
    private function cycle(int $daysAgo = 62, string $status = 'active'): CropCycle
    {
        $farm = Farm::create(['name' => 'Trooms', 'location' => 'Naivasha', 'size_acres' => 40]);
        $block = Block::create(['farm_id' => $farm->id, 'name' => 'Block A', 'size_acres' => 2]);
        $crop = Crop::create(['name' => 'Tomato', 'crop_type' => 'Vegetable']);

        $template = CropCycleTemplate::create([
            'crop_id' => $crop->id,
            'crop_name' => 'Tomato',
            'variety' => 'Roma',
            'total_cycle_days' => 90,
        ]);

        $stage = $template->stages()->create([
            'stage_name' => 'Fruiting',
            'start_day_offset' => 55,
            'end_day_offset' => 75,
            'sort_order' => 1,
        ]);

        $template->schedulePoints()->create([
            'day_offset' => 60,
            'activity_type' => 'spray',
            'product_name' => 'Mancozeb fungicide',
            'purpose' => 'blight prevention',
            'dosage' => '2kg/ha',
            'crop_cycle_stage_id' => $stage->id,
        ]);

        $template->schedulePoints()->create([
            'day_offset' => 85,
            'activity_type' => 'harvest_check',
            'purpose' => 'maturity check',
        ]);

        return CropCycle::create([
            'block_id' => $block->id,
            'crop_id' => $crop->id,
            'crop_cycle_template_id' => $template->id,
            'season_name' => 'Long Rains 2026',
            'planting_date' => now()->subDays($daysAgo)->toDateString(),
            'status' => $status,
        ]);
    }

    public function test_a_schedule_point_that_has_come_due_raises_a_task_for_the_field(): void
    {
        $cycle = $this->cycle();
        $supervisor = $this->supervisor();

        $result = app(ScheduleReminderService::class)->run();

        // Day 60 has passed on a 62-day-old cycle; day 85 has not.
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, Task::count());

        $task = Task::first();
        $this->assertSame('schedule_point', $task->source);
        $this->assertSame($supervisor->id, $task->assigned_to);
        $this->assertStringContainsString('Mancozeb fungicide', $task->description);
        $this->assertSame(
            $cycle->planting_date->copy()->addDays(60)->toDateString(),
            $task->due_date->toDateString()
        );
    }

    public function test_running_twice_does_not_raise_the_same_reminder_again(): void
    {
        $this->cycle();
        $this->supervisor();

        app(ScheduleReminderService::class)->run();
        $second = app(ScheduleReminderService::class)->run();

        // The field must not be told twice to do the same spray.
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, Task::count());
    }

    public function test_a_point_already_logged_never_becomes_a_task(): void
    {
        $cycle = $this->cycle();
        $user = $this->supervisor();
        $point = $cycle->template->schedulePoints->firstWhere('day_offset', 60);

        $cycle->activities()->create([
            'crop_cycle_schedule_point_id' => $point->id,
            'activity_type' => 'spray',
            'product_name' => 'Mancozeb fungicide',
            'performed_date' => now()->subDays(3)->toDateString(),
            'performed_by' => $user->id,
        ]);

        $result = app(ScheduleReminderService::class)->run();

        $this->assertSame(0, $result['created']);
        $this->assertSame(0, Task::count());
    }

    public function test_only_active_cycles_running_a_template_are_scheduled(): void
    {
        $this->cycle(62, 'planned');
        $this->supervisor();

        $result = app(ScheduleReminderService::class)->run();

        // A planned cycle has not been sown, so nothing is due on it.
        $this->assertSame(0, $result['cycles']);
        $this->assertSame(0, Task::count());
    }

    public function test_the_current_stage_follows_the_calendar(): void
    {
        $cycle = $this->cycle();
        $this->supervisor();

        $this->assertNull($cycle->current_stage_id);

        app(ScheduleReminderService::class)->run();

        // Day 62 sits inside Fruiting (day 55-75).
        $this->assertSame('Fruiting', $cycle->fresh()->currentStage->stage_name);
    }

    public function test_a_task_left_open_past_its_date_escalates_once(): void
    {
        $this->cycle();
        $this->supervisor();
        User::factory()->create(['role' => 'horticulture_manager', 'is_active' => true]);

        $first = app(ScheduleReminderService::class)->run();
        $this->assertSame(1, $first['escalated']);
        $this->assertNotNull(Task::first()->escalated_at);

        // Escalation is a one-off: the manager is not chased daily.
        $second = app(ScheduleReminderService::class)->run();
        $this->assertSame(0, $second['escalated']);
    }

    public function test_logging_the_work_closes_the_task_and_books_the_cost(): void
    {
        $cycle = $this->cycle();
        $user = $this->supervisor();
        $point = $cycle->template->schedulePoints->firstWhere('day_offset', 60);

        app(ScheduleReminderService::class)->run();

        $this->actingAs($user)->post(route('crop-cycles.activities.store', $cycle), [
            'crop_cycle_schedule_point_id' => $point->id,
            'activity_type' => 'spray',
            'performed_date' => now()->subDay()->toDateString(),
            'cost_kes' => 3500,
        ])->assertSessionHasNoErrors();

        $this->assertSame('done', Task::first()->status);
        // The cost reaches the cycle without a separate expense being filed.
        $this->assertSame(3500.0, $cycle->fresh()->actualCost());
        // The product defaults to whatever the plan called for.
        $this->assertSame('Mancozeb fungicide', $cycle->activities()->first()->product_name);
    }

    public function test_the_same_scheduled_task_cannot_be_logged_twice(): void
    {
        $cycle = $this->cycle();
        $user = $this->supervisor();
        $point = $cycle->template->schedulePoints->firstWhere('day_offset', 60);

        $payload = [
            'crop_cycle_schedule_point_id' => $point->id,
            'activity_type' => 'spray',
            'performed_date' => now()->subDay()->toDateString(),
            'cost_kes' => 3500,
        ];

        $this->actingAs($user)->post(route('crop-cycles.activities.store', $cycle), $payload);
        $this->actingAs($user)->post(route('crop-cycles.activities.store', $cycle), $payload);

        $this->assertSame(1, $cycle->activities()->count());
        $this->assertSame(3500.0, $cycle->fresh()->actualCost());
    }

    public function test_removing_a_logged_activity_reopens_its_task_and_reverses_the_cost(): void
    {
        $cycle = $this->cycle();
        $user = $this->supervisor();
        $point = $cycle->template->schedulePoints->firstWhere('day_offset', 60);

        app(ScheduleReminderService::class)->run();

        $this->actingAs($user)->post(route('crop-cycles.activities.store', $cycle), [
            'crop_cycle_schedule_point_id' => $point->id,
            'activity_type' => 'spray',
            'performed_date' => now()->subDay()->toDateString(),
            'cost_kes' => 3500,
        ]);

        $activity = $cycle->activities()->first();

        $this->actingAs($user)->delete(route('crop-cycles.activities.destroy', [$cycle, $activity]));

        // Undoing the record must not leave the work looking done.
        $this->assertSame('pending', Task::first()->status);
        $this->assertSame(0.0, $cycle->fresh()->actualCost());
    }

    public function test_the_schedule_reports_what_is_done_overdue_and_upcoming(): void
    {
        $cycle = $this->cycle();
        $user = $this->supervisor();
        $point = $cycle->template->schedulePoints->firstWhere('day_offset', 60);

        $cycle->activities()->create([
            'crop_cycle_schedule_point_id' => $point->id,
            'activity_type' => 'spray',
            'performed_date' => $cycle->planting_date->copy()->addDays(58)->toDateString(),
            'performed_by' => $user->id,
        ]);

        $schedule = $cycle->fresh()->resolvedSchedule();

        $this->assertSame('done', $schedule->firstWhere('point.day_offset', 60)['status']);
        $this->assertSame('upcoming', $schedule->firstWhere('point.day_offset', 85)['status']);
        // Sprayed two days early, so the one point that has come due was on time.
        $this->assertSame(1.0, $cycle->fresh()->sprayComplianceRate());
    }

    public function test_the_setup_wizard_attaches_a_template_and_takes_its_cycle_length(): void
    {
        $crop = Crop::create(['name' => 'Tomato', 'crop_type' => 'Vegetable', 'days_to_maturity' => 70]);
        $template = CropCycleTemplate::create([
            'crop_id' => $crop->id,
            'crop_name' => 'Tomato',
            'total_cycle_days' => 90,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'horticulture_manager', 'is_active' => true]))
            ->post(route('setup'), [
                'farm_mode' => 'new', 'farm_name' => 'Trooms', 'farm_location' => 'Naivasha', 'farm_size_acres' => 10,
                'block_mode' => 'new', 'block_name' => 'Block A', 'block_size_acres' => 2, 'block_already_prepared' => 1,
                'crop_mode' => 'existing', 'existing_crop_id' => $crop->id,
                'crop_cycle_template_id' => $template->id,
                'season_name' => 'Long Rains 2026', 'planting_date' => '2026-07-01',
                'labour_budget' => 1000, 'input_budget' => 1000, 'irrigation_budget' => 0, 'overhead_budget' => 0,
            ])->assertSessionHasNoErrors();

        $cycle = CropCycle::firstWhere('season_name', 'Long Rains 2026');

        $this->assertSame($template->id, $cycle->crop_cycle_template_id);
        // The template's 90 days is the plan of record, over the crop's nominal 70.
        $this->assertSame('2026-09-29', $cycle->expected_harvest_date->toDateString());
    }
}
