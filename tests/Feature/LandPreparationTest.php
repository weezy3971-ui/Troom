<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\Expense;
use App\Models\Farm;
use App\Models\LandPreparation;
use App\Models\User;
use App\Support\LandPrepProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandPreparationTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): User
    {
        return User::factory()->create(['role' => 'horticulture_manager', 'is_active' => true]);
    }

    private function farm(): Farm
    {
        return Farm::create(['name' => 'Trooms Naivasha', 'location' => 'Naivasha', 'size_acres' => 40]);
    }

    /** A cycle runs a template, so one has to exist before a cycle can be created. */
    private function template(Crop $crop): \App\Models\CropCycleTemplate
    {
        return \App\Models\CropCycleTemplate::create([
            'crop_id' => $crop->id,
            'crop_name' => $crop->name,
            'total_cycle_days' => 90,
        ]);
    }

    private function block(): Block
    {
        return Block::create([
            'farm_id' => $this->farm()->id,
            'name' => 'Block C',
            'size_acres' => 2.5,
            'soil_type' => 'Loam',
        ]);
    }

    public function test_adding_a_block_leads_to_preparing_it(): void
    {
        $farm = $this->farm();

        $response = $this->actingAs($this->manager())->post(route('blocks.store'), [
            'farm_id' => $farm->id,
            'name' => 'Block D',
            'size_acres' => 3,
            'soil_type' => 'Clay loam',
        ]);

        $block = Block::firstWhere('name', 'Block D');

        // The next step after adding a block is preparation of that block, and
        // that lands on the worksheet itself rather than a screen in front of it.
        $response->assertRedirect(route('land-preparations.open', $block));

        $this->actingAs($this->manager())
            ->get(route('land-preparations.open', $block))
            ->assertRedirect(route('land-preparations.show', LandPreparation::firstWhere('block_id', $block->id)));
    }

    public function test_opening_a_block_never_prepared_starts_the_round_and_shows_the_checklist(): void
    {
        $block = $this->block();

        $this->actingAs($this->manager())
            ->get(route('land-preparations.open', $block))
            ->assertRedirect();

        $prep = LandPreparation::firstWhere('block_id', $block->id);
        $this->assertNotNull($prep);
        $this->assertCount(count(LandPrepProgram::tasks()), $prep->tasks);

        $this->actingAs($this->manager())
            ->get(route('land-preparations.show', $prep))
            ->assertOk()
            ->assertSee('Preparation steps')
            ->assertSee('Soil sampling &amp; test', false);
    }

    public function test_opening_the_same_block_twice_returns_the_same_round(): void
    {
        $block = $this->block();
        $actor = $this->manager();

        $this->actingAs($actor)->get(route('land-preparations.open', $block));
        $this->actingAs($actor)->get(route('land-preparations.open', $block));

        // Landing on the worksheet must not quietly stack up empty rounds.
        $this->assertSame(1, LandPreparation::where('block_id', $block->id)->count());
    }

    public function test_a_new_round_is_only_started_when_explicitly_asked_for(): void
    {
        $block = $this->block();
        $actor = $this->manager();

        $this->actingAs($actor)->get(route('land-preparations.open', $block));
        $this->actingAs($actor)->post(route('land-preparations.store', $block));

        $this->assertSame(2, LandPreparation::where('block_id', $block->id)->count());

        // Opening the block now goes to the newest round.
        $newest = LandPreparation::where('block_id', $block->id)->latest('id')->first();
        $this->actingAs($actor)
            ->get(route('land-preparations.open', $block))
            ->assertRedirect(route('land-preparations.show', $newest));
    }

    public function test_a_read_only_role_opening_an_unprepared_block_is_sent_back_not_given_a_round(): void
    {
        $block = $this->block();

        $this->actingAs(User::factory()->create(['role' => 'sales_officer', 'is_active' => true]))
            ->get(route('land-preparations.open', $block))
            ->assertRedirect(route('blocks.show', $block));

        $this->assertSame(0, LandPreparation::where('block_id', $block->id)->count());
    }

    public function test_starting_preparation_copies_the_standard_checklist(): void
    {
        $block = $this->block();

        $this->actingAs($this->manager())
            ->post(route('land-preparations.store', $block))
            ->assertSessionHasNoErrors();

        $prep = LandPreparation::firstWhere('block_id', $block->id);

        $this->assertNotNull($prep);
        $this->assertSame('planned', $prep->status);
        $this->assertCount(count(LandPrepProgram::tasks()), $prep->tasks);
        $this->assertSame('Clear the land', $prep->tasks->first()->name);
    }

    public function test_the_checklist_is_a_copy_so_revising_the_standard_never_rewrites_field_records(): void
    {
        $block = $this->block();
        $prep = LandPreparation::startFor($block);

        $task = $prep->tasks->first();
        $task->update(['name' => 'Clear the land (edited on site)']);

        $this->assertSame('Clear the land', LandPrepProgram::tasks()[0]['name']);
        $this->assertSame('Clear the land (edited on site)', $prep->fresh()->tasks->first()->name);
    }

    public function test_ticking_a_step_moves_a_planned_round_into_progress(): void
    {
        $block = $this->block();
        $prep = LandPreparation::startFor($block);
        $task = $prep->tasks->first();

        $this->assertSame('planned', $prep->status);

        $this->actingAs($this->manager())
            ->put(route('land-preparations.tasks.update', $task), ['status' => 'done'])
            ->assertSessionHasNoErrors();

        $prep->refresh();
        $this->assertSame('in_progress', $prep->status);
        $this->assertNotNull($prep->started_on);
        $this->assertTrue($task->fresh()->isDone());
        $this->assertNotNull($task->fresh()->done_on);
    }

    public function test_a_skipped_step_does_not_count_as_outstanding(): void
    {
        $prep = LandPreparation::startFor($this->block());
        $total = $prep->tasks->count();

        foreach ($prep->tasks as $i => $task) {
            $task->update(['status' => $i === 0 ? 'skipped' : 'done']);
        }

        $prep = $prep->fresh()->load('tasks');

        // Not every block needs liming or drainage work, so a round can finish
        // honestly without pretending the step happened.
        $this->assertSame(0, $prep->outstandingCount());
        $this->assertSame($total - 1, $prep->doneCount());
        $this->assertSame(100, $prep->percentComplete());
    }

    public function test_completing_a_round_records_the_ready_to_plant_date(): void
    {
        $prep = LandPreparation::startFor($this->block());

        $this->actingAs($this->manager())
            ->put(route('land-preparations.update', $prep), ['status' => 'complete'])
            ->assertSessionHasNoErrors();

        // The date the block became plantable is the one date this round exists
        // to capture, so completing without one fills it in rather than losing it.
        $this->assertNotNull($prep->fresh()->completed_on);
    }

    public function test_prep_spend_attaches_to_the_round_and_reaches_the_planting(): void
    {
        $block = $this->block();
        $prep = LandPreparation::startFor($block);

        $this->actingAs($this->manager())->post(route('expenses.store'), [
            'category' => 'labour_casual',
            'amount' => 4500,
            'expense_date' => '2026-07-20',
            'description' => 'Casual labour for ploughing',
            'block_id' => $block->id,
            'land_preparation_id' => $prep->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame(4500.0, $prep->fresh()->totalCost());

        $crop = Crop::create(['name' => 'Capsicum', 'crop_type' => 'vegetable']);
        $cycle = CropCycle::create([
            'block_id' => $block->id,
            'crop_id' => $crop->id,
            'season_name' => 'Jul 2026',
            'planting_date' => '2026-07-25',
            'status' => 'planned',
        ]);

        $this->actingAs($this->manager())->put(route('land-preparations.update', $prep), [
            'status' => 'complete',
            'crop_cycle_id' => $cycle->id,
        ])->assertSessionHasNoErrors();

        // The spend now belongs to a planting instead of floating on the block.
        $this->assertTrue($cycle->fresh()->landPreparation->is($prep));
        $this->assertSame(4500.0, (float) $cycle->fresh()->landPreparation->totalCost());
    }

    public function test_a_round_cannot_be_attributed_to_a_cycle_on_another_block(): void
    {
        $prep = LandPreparation::startFor($this->block());

        $otherBlock = Block::create([
            'farm_id' => $this->farm()->id, 'name' => 'Block Z', 'size_acres' => 1,
        ]);
        $crop = Crop::create(['name' => 'Kale', 'crop_type' => 'vegetable']);
        $foreign = CropCycle::create([
            'block_id' => $otherBlock->id, 'crop_id' => $crop->id,
            'season_name' => 'Jul 2026', 'planting_date' => '2026-07-25', 'status' => 'planned',
        ]);

        $this->actingAs($this->manager())
            ->from(route('land-preparations.show', $prep))
            ->put(route('land-preparations.update', $prep), [
                'status' => 'complete',
                'crop_cycle_id' => $foreign->id,
            ])
            ->assertSessionHasErrors('crop_cycle_id');

        $this->assertNull($prep->fresh()->crop_cycle_id);
    }

    public function test_a_role_without_field_write_access_cannot_record_preparation(): void
    {
        $prep = LandPreparation::startFor($this->block());

        $this->actingAs(User::factory()->create(['role' => 'sales_officer', 'is_active' => true]))
            ->put(route('land-preparations.tasks.update', $prep->tasks->first()), ['status' => 'done'])
            ->assertForbidden();
    }

    public function test_the_block_page_offers_preparation_and_then_shows_it(): void
    {
        $block = $this->block();
        $actor = $this->manager();

        $this->actingAs($actor)
            ->get(route('blocks.show', $block))
            ->assertOk()
            ->assertSee('Prepare Block');

        $prep = LandPreparation::startFor($block);
        Expense::create([
            'category' => 'fuel', 'amount' => 1200, 'expense_date' => '2026-07-20',
            'description' => 'Tractor fuel', 'block_id' => $block->id, 'land_preparation_id' => $prep->id,
        ]);

        $this->actingAs($actor)
            ->get(route('blocks.show', $block))
            ->assertOk()
            ->assertSee('Land Preparation')
            ->assertSee('1,200.00')
            ->assertSee('not yet attributed to a planting');
    }

    public function test_land_prep_guidance_is_listed_in_the_sources_register(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner', 'is_active' => true]))
            ->get(route('information-sources.index'))
            ->assertOk()
            ->assertSee('Land preparation');
    }

    public function test_creating_a_cycle_attaches_a_preparation_round_to_it(): void
    {
        $block = $this->block();
        $crop = Crop::create(['name' => 'Capsicum', 'crop_type' => 'Vegetable']);

        $this->actingAs($this->manager())->post(route('crop-cycles.store'), [
            'block_id' => $block->id,
            'crop_id' => $crop->id,
            'crop_cycle_template_id' => $this->template($crop)->id,
            'season_name' => 'Long Rains 2026',
            'planting_date' => '2026-07-25',
        ])->assertSessionHasNoErrors();

        $cycle = CropCycle::firstWhere('season_name', 'Long Rains 2026');

        $this->assertNotNull($cycle->landPreparation);
        $this->assertSame($block->id, $cycle->landPreparation->block_id);
    }

    public function test_an_existing_unattributed_round_is_reused_rather_than_duplicated(): void
    {
        $block = $this->block();
        $existing = LandPreparation::startFor($block);
        $crop = Crop::create(['name' => 'Capsicum', 'crop_type' => 'Vegetable']);

        $this->actingAs($this->manager())->post(route('crop-cycles.store'), [
            'block_id' => $block->id,
            'crop_id' => $crop->id,
            'crop_cycle_template_id' => $this->template($crop)->id,
            'season_name' => 'Long Rains 2026',
        ]);

        // Prep already paid for on this block belongs to the planting that follows.
        $this->assertSame(1, LandPreparation::where('block_id', $block->id)->count());
        $this->assertSame(
            CropCycle::firstWhere('season_name', 'Long Rains 2026')->id,
            $existing->fresh()->crop_cycle_id
        );
    }

    public function test_a_cycle_cannot_be_activated_while_preparation_is_outstanding(): void
    {
        $cycle = $this->budgetedCycle();
        LandPreparation::attachTo($cycle);

        $this->actingAs($this->manager())
            ->post(route('crop-cycles.activate', $cycle))
            ->assertRedirect(route('land-preparations.show', $cycle->fresh()->landPreparation));

        $this->assertSame('planned', $cycle->fresh()->status);
    }

    public function test_a_cycle_activates_once_preparation_is_complete(): void
    {
        $cycle = $this->budgetedCycle();
        $prep = LandPreparation::attachTo($cycle);

        $this->actingAs($this->manager())
            ->put(route('land-preparations.update', $prep), ['status' => 'complete']);

        $this->actingAs($this->manager())->post(route('crop-cycles.activate', $cycle));

        $this->assertSame('active', $cycle->fresh()->status);
    }

    public function test_preparation_can_be_recorded_as_not_required_with_a_reason(): void
    {
        $cycle = $this->budgetedCycle();
        $prep = LandPreparation::attachTo($cycle);

        // A reason is mandatory: this is the only way a block gets planted with
        // no preparation on record.
        $this->actingAs($this->manager())
            ->from(route('land-preparations.show', $prep))
            ->put(route('land-preparations.waive', $prep), ['notes' => ''])
            ->assertSessionHasErrors('notes');

        $this->actingAs($this->manager())
            ->put(route('land-preparations.waive', $prep), ['notes' => 'Ploughed by contractor last month.'])
            ->assertSessionHasNoErrors();

        $this->assertSame('not_required', $prep->fresh()->status);

        $this->actingAs($this->manager())->post(route('crop-cycles.activate', $cycle));
        $this->assertSame('active', $cycle->fresh()->status);
    }

    public function test_a_cycle_with_no_preparation_attached_still_activates(): void
    {
        // Cycles created before this rule existed must not be stranded.
        $cycle = $this->budgetedCycle();

        $this->actingAs($this->manager())->post(route('crop-cycles.activate', $cycle));

        $this->assertSame('active', $cycle->fresh()->status);
    }

    public function test_the_setup_wizard_leaves_a_cycle_planned_until_the_block_is_prepared(): void
    {
        $farm = $this->farm();

        $this->actingAs($this->manager())->post(route('setup'), [
            'farm_mode' => 'existing', 'existing_farm_id' => $farm->id,
            'block_mode' => 'new', 'block_name' => 'Block N', 'block_size_acres' => 2,
            'crop_mode' => 'new', 'crop_name' => 'Capsicum', 'crop_type' => 'Vegetable',
            'season_name' => 'Long Rains 2026', 'planting_date' => '2026-08-01',
            'labour_budget' => 1000, 'input_budget' => 1000,
            'irrigation_budget' => 0, 'overhead_budget' => 0,
        ])->assertSessionHasNoErrors();

        $cycle = CropCycle::firstWhere('season_name', 'Long Rains 2026');

        $this->assertSame('planned', $cycle->status);
        $this->assertNotNull($cycle->landPreparation);
    }

    public function test_the_setup_wizard_activates_when_the_block_is_declared_already_prepared(): void
    {
        $farm = $this->farm();

        $this->actingAs($this->manager())->post(route('setup'), [
            'farm_mode' => 'existing', 'existing_farm_id' => $farm->id,
            'block_mode' => 'new', 'block_name' => 'Block N', 'block_size_acres' => 2,
            'block_already_prepared' => 1,
            'crop_mode' => 'new', 'crop_name' => 'Capsicum', 'crop_type' => 'Vegetable',
            'season_name' => 'Long Rains 2026', 'planting_date' => '2026-08-01',
            'labour_budget' => 1000, 'input_budget' => 1000,
            'irrigation_budget' => 0, 'overhead_budget' => 0,
        ])->assertSessionHasNoErrors();

        $cycle = CropCycle::firstWhere('season_name', 'Long Rains 2026');

        $this->assertSame('active', $cycle->status);
        $this->assertSame('not_required', $cycle->landPreparation->status);
    }

    /** A cycle with a budget set, ready to activate but for land prep. */
    private function budgetedCycle(): CropCycle
    {
        $block = $this->block();
        $crop = Crop::create(['name' => 'Capsicum', 'crop_type' => 'Vegetable']);

        $cycle = CropCycle::create([
            'block_id' => $block->id, 'crop_id' => $crop->id,
            'season_name' => 'Long Rains 2026', 'planting_date' => '2026-07-25',
            'status' => 'planned',
        ]);

        $cycle->seasonalBudget()->create([
            'labour_budget' => 1000, 'input_budget' => 1000,
            'irrigation_budget' => 0, 'overhead_budget' => 0, 'total_budget' => 2000,
        ]);

        return $cycle->fresh();
    }
}
