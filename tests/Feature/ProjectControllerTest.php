<?php

use App\Models\DecisionRecord;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\Prototype;
use App\Models\SecurityNote;
use App\Models\User;
use App\Models\VettingItem;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests cannot reach the projects', function () {
    auth()->logout();

    $this->get(route('projects.index'))->assertRedirect(route('login'));
    $this->post(route('projects.store'), [])->assertRedirect(route('login'));
});

test('it creates a project and upper-cases the prefix', function () {
    $this->post(route('projects.store'), [
        'name' => 'Vision Next Gen',
        'prefix' => 'vng',
        'description' => 'The **next** platform.',
    ])->assertRedirect(route('projects.show', Project::sole()));

    expect(Project::sole()->prefix)->toBe('VNG');
});

test('it rejects a duplicate or malformed prefix', function () {
    Project::factory()->create(['prefix' => 'VNG']);

    $this->post(route('projects.store'), ['name' => 'Other', 'prefix' => 'VNG'])
        ->assertSessionHasErrors('prefix');

    $this->post(route('projects.store'), ['name' => 'Other', 'prefix' => 'has space'])
        ->assertSessionHasErrors('prefix');

    expect(Project::count())->toBe(1);
});

test('the index counts what each project holds', function () {
    $project = Project::factory()->create();

    DecisionRecord::factory()->count(2)->create(['project_id' => $project->id]);
    VettingItem::factory()->create(['project_id' => $project->id]);
    Prototype::factory()->count(3)->create(['project_id' => $project->id]);
    SecurityNote::factory()->create(['project_id' => $project->id]);
    ProjectNote::factory()->count(2)->create(['project_id' => $project->id]);

    $this->get(route('projects.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('projects/index')
            ->has('projects', 1)
            ->where('projects.0.decision_records_count', 2)
            ->where('projects.0.vetting_items_count', 1)
            ->where('projects.0.prototypes_count', 3)
            ->where('projects.0.security_notes_count', 1)
            ->where('projects.0.notes_count', 2));
});

test('the project page gathers everything filed under it', function () {
    $project = Project::factory()->create();
    $other = Project::factory()->create();

    DecisionRecord::factory()->create(['project_id' => $project->id]);
    DecisionRecord::factory()->create(['project_id' => $other->id]);
    VettingItem::factory()->create(['project_id' => $project->id]);
    Prototype::factory()->create(['project_id' => $project->id]);
    SecurityNote::factory()->create(['project_id' => $project->id]);
    ProjectNote::factory()->create(['project_id' => $project->id, 'body' => 'A **note**.']);
    DecisionRecord::factory()->create();

    $this->get(route('projects.show', $project))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('projects/show')
            ->has('decisions', 1)
            ->has('vettingItems', 1)
            ->has('prototypes', 1)
            ->has('securityNotes', 1)
            ->has('notes', 1)
            ->where('notes.0.html', fn (string $html) => str_contains($html, '<strong>note</strong>')));
});

test('a decision takes its prefix from the project it is filed under', function () {
    $project = Project::factory()->create(['prefix' => 'VNG']);

    $record = DecisionRecord::factory()->create([
        'project_id' => $project->id,
        'project_prefix' => 'TYPO',
        'category' => 'ARCH',
        'sequence' => 1,
    ]);

    expect($record->refresh()->document_id)->toBe('VNG-ARCH-001');
});

test('a decision with no project keeps the prefix it was given', function () {
    $record = DecisionRecord::factory()->create([
        'project_id' => null,
        'project_prefix' => 'ADHOC',
        'category' => 'ARCH',
        'sequence' => 2,
    ]);

    expect($record->document_id)->toBe('ADHOC-ARCH-002');
});

test('renaming a project prefix re-stamps its decisions', function () {
    $project = Project::factory()->create(['prefix' => 'VNG']);
    $record = DecisionRecord::factory()->create([
        'project_id' => $project->id, 'category' => 'ARCH', 'sequence' => 1,
    ]);

    $this->put(route('projects.update', $project), [
        'name' => $project->name,
        'prefix' => 'VNX',
    ])->assertRedirect(route('projects.show', $project));

    expect($record->refresh()->document_id)->toBe('VNX-ARCH-001');
});

test('removing a project keeps the work filed under it', function () {
    $project = Project::factory()->create();
    $record = DecisionRecord::factory()->create(['project_id' => $project->id]);
    $vetting = VettingItem::factory()->create(['project_id' => $project->id]);
    ProjectNote::factory()->create(['project_id' => $project->id]);

    $this->delete(route('projects.destroy', $project))
        ->assertRedirect(route('projects.index'));

    expect(Project::count())->toBe(0)
        ->and(ProjectNote::count())->toBe(0)
        ->and($record->refresh()->project_id)->toBeNull()
        ->and($vetting->refresh()->project_id)->toBeNull();
});

test('a module record can be filed under a project through its own form', function () {
    $project = Project::factory()->create(['prefix' => 'VNG']);

    $this->post(route('vetting.store'), [
        'title' => 'Move reporting to a queue',
        'source_type' => 'meeting',
        'date_raised' => '2026-09-01',
        'proposal_description' => 'Async it.',
        'status' => 'new',
        'project_id' => $project->id,
    ])->assertSessionHasNoErrors();

    expect(VettingItem::sole()->project_id)->toBe($project->id);
});

test('a record cannot be filed under a project that does not exist', function () {
    $this->post(route('vetting.store'), [
        'title' => 'Bad project',
        'source_type' => 'meeting',
        'date_raised' => '2026-09-01',
        'proposal_description' => 'Async it.',
        'status' => 'new',
        'project_id' => 9999,
    ])->assertSessionHasErrors('project_id');
});

test('a read-only account can read projects but not change them', function () {
    auth()->logout();
    $this->actingAs(User::factory()->readOnly()->create());

    $project = Project::factory()->create();

    $this->get(route('projects.index'))->assertOk();
    $this->get(route('projects.show', $project))->assertOk();

    $this->post(route('projects.store'), ['name' => 'x', 'prefix' => 'X'])->assertForbidden();
    $this->post(route('projects.notes.store', $project), ['title' => 'a', 'body' => 'b'])
        ->assertForbidden();
    $this->delete(route('projects.destroy', $project))->assertForbidden();

    expect(Project::count())->toBe(1);
});

test('notes can be added, rewritten and removed', function () {
    $project = Project::factory()->create();

    $this->from(route('projects.show', $project))
        ->post(route('projects.notes.store', $project), [
            'title' => 'Kickoff',
            'body' => 'Met the team.',
        ])->assertRedirect(route('projects.show', $project));

    $note = ProjectNote::sole();

    expect($note->project_id)->toBe($project->id);

    $this->put(route('projects.notes.update', $note), [
        'title' => 'Kickoff notes',
        'body' => 'Met the team, agreed the scope.',
    ]);

    expect($note->refresh()->title)->toBe('Kickoff notes');

    $this->delete(route('projects.notes.destroy', $note));

    expect(ProjectNote::count())->toBe(0);
});

test('a note needs a title and a body', function () {
    $project = Project::factory()->create();

    $this->post(route('projects.notes.store', $project), ['title' => '', 'body' => ''])
        ->assertSessionHasErrors(['title', 'body']);

    expect(ProjectNote::count())->toBe(0);
});
