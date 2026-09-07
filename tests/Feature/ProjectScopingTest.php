<?php

use App\Models\DecisionRecord;
use App\Models\Project;
use App\Models\Prototype;
use App\Models\RadarItem;
use App\Models\SecurityNote;
use App\Models\User;
use App\Models\VettingItem;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('each module index can be narrowed to one project', function () {
    $project = Project::factory()->create();

    DecisionRecord::factory()->count(2)->create(['project_id' => $project->id]);
    DecisionRecord::factory()->create();
    VettingItem::factory()->create(['project_id' => $project->id]);
    VettingItem::factory()->count(2)->create();
    Prototype::factory()->create(['project_id' => $project->id]);
    Prototype::factory()->create();
    SecurityNote::factory()->create(['project_id' => $project->id]);
    SecurityNote::factory()->create();

    $this->get(route('decisions.index', ['project' => $project->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('records', 2)
            ->where('projectFilter', (string) $project->id));

    $this->get(route('vetting.index', ['project' => $project->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('items', 1));

    $this->get(route('prototypes.index', ['project' => $project->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('prototypes', 1));

    $this->get(route('security-notes.index', ['project' => $project->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('notes', 1));
});

test('work with no project can be singled out', function () {
    $project = Project::factory()->create();

    VettingItem::factory()->count(2)->create(['project_id' => $project->id]);
    VettingItem::factory()->create();

    $this->get(route('vetting.index', ['project' => 'none']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('items', 1)
            ->where('projectFilter', 'none'));
});

test('no filter shows everything', function () {
    VettingItem::factory()->create(['project_id' => Project::factory()->create()->id]);
    VettingItem::factory()->create();

    $this->get(route('vetting.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('items', 2)
            ->where('projectFilter', '')
            ->has('projectFilters', 3));
});

test('the module filter options do not clash with the form options', function () {
    Project::factory()->create();

    $this->get(route('prototypes.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            // the filter list carries "All projects" and "No project" as well
            ->has('projectFilters', 3)
            ->has('projects', 1)
            ->where('projectFilters.0.value', '')
            ->where('projectFilters.1.value', 'none'));
});

test('the projects index hides archived work until asked', function () {
    Project::factory()->create(['name' => 'Running']);
    Project::factory()->archived()->create(['name' => 'Finished']);

    $this->get(route('projects.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('projects', 1)
            ->where('projects.0.name', 'Running')
            ->where('archivedCount', 1)
            ->where('showingArchived', false));

    $this->get(route('projects.index', ['archived' => 1]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('projects', 1)
            ->where('projects.0.name', 'Finished')
            ->where('showingArchived', true));
});

test('a project can be archived and brought back', function () {
    $project = Project::factory()->create();

    $this->from(route('projects.show', $project))
        ->patch(route('projects.archive', $project), ['archived' => true])
        ->assertRedirect(route('projects.show', $project));

    expect($project->refresh()->isArchived())->toBeTrue();

    $this->patch(route('projects.archive', $project), ['archived' => false]);

    expect($project->refresh()->isArchived())->toBeFalse();
});

test('archiving keeps the work filed under the project', function () {
    $project = Project::factory()->create();
    $record = DecisionRecord::factory()->create(['project_id' => $project->id]);

    $this->patch(route('projects.archive', $project), ['archived' => true]);

    expect($record->refresh()->project_id)->toBe($project->id);
});

test('an archived project can still be picked, and says so', function () {
    Project::factory()->archived()->create(['prefix' => 'OLD', 'name' => 'Retired']);

    $this->get(route('vetting.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('projects', 1)
            ->where('projects.0.label', fn (string $label) => str_contains($label, '(archived)')));
});

test('a read-only account cannot archive', function () {
    auth()->logout();
    $this->actingAs(User::factory()->readOnly()->create());

    $project = Project::factory()->create();

    $this->patch(route('projects.archive', $project), ['archived' => true])->assertForbidden();

    expect($project->refresh()->isArchived())->toBeFalse();
});

test('a radar item can become a prototype instead of a vetting item', function () {
    $item = RadarItem::factory()->create([
        'title' => 'Try the new queue driver',
        'summary' => 'Batches jobs before dispatch.',
    ]);

    $this->post(route('radar.promote', $item), ['target' => 'prototype'])
        ->assertRedirect(route('prototypes.edit', Prototype::sole()));

    $prototype = Prototype::sole();

    expect($prototype->title)->toBe('Try the new queue driver')
        ->and($prototype->hypothesis)->toContain('Batches jobs before dispatch.')
        ->and($prototype->project_id)->toBeNull()
        ->and($item->refresh()->triage_status->value)->toBe('relevant');

    expect($prototype->incomingItemLinks()->count())->toBe(1);
});

test('an item can go to both a prototype and the vetting log, but not twice to either', function () {
    $item = RadarItem::factory()->create();

    $this->post(route('radar.promote', $item), ['target' => 'prototype']);
    $this->post(route('radar.promote', $item), ['target' => 'vetting']);

    expect(Prototype::count())->toBe(1)
        ->and(VettingItem::count())->toBe(1);

    $this->post(route('radar.promote', $item), ['target' => 'prototype']);
    $this->post(route('radar.promote', $item), ['target' => 'vetting']);

    expect(Prototype::count())->toBe(1)
        ->and(VettingItem::count())->toBe(1);
});

test('the queue says which items already became work', function () {
    $item = RadarItem::factory()->create();

    $this->post(route('radar.promote', $item), ['target' => 'prototype']);

    $this->get(route('radar.index'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $row = collect($page->toArray()['props']['items']['data'])->firstOrFail();

            expect($row['prototyped'])->toBeTrue()
                ->and($row['promoted'])->toBeFalse();
        });
});
