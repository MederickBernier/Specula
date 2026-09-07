<?php

use App\Enums\PrototypeStatus;
use App\Enums\SecurityNoteStatus;
use App\Enums\VettingStatus;
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

/**
 * @return list<array<string, mixed>>
 */
function timelineFor(AssertableInertia $page): array
{
    return $page->toArray()['props']['events'];
}

test('guests cannot read a timeline', function () {
    auth()->logout();

    $project = Project::factory()->create();

    $this->get(route('projects.timeline', $project))->assertRedirect(route('login'));
});

test('a new project has only its own beginning on the timeline', function () {
    $project = Project::factory()->create();

    $this->get(route('projects.timeline', $project))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $events = timelineFor($page);

            expect($events)->toHaveCount(1)
                ->and($events[0]['event'])->toBe('Project started');
        });
});

test('it gathers every module, newest first', function () {
    $project = Project::factory()->create(['created_at' => '2026-01-01']);

    DecisionRecord::factory()->create([
        'project_id' => $project->id,
        'category' => 'ARCH',
        'sequence' => 1,
        'title' => 'Pick a stack',
        'created_at' => '2026-03-01',
    ]);
    VettingItem::factory()->create([
        'project_id' => $project->id,
        'title' => 'Queue the reports',
        'date_raised' => '2026-02-01',
    ]);
    Prototype::factory()->create([
        'project_id' => $project->id,
        'title' => 'Spike the queue',
        'date_started' => '2026-02-15',
    ]);
    SecurityNote::factory()->create([
        'project_id' => $project->id,
        'title' => 'Creds in logs',
        'date_flagged' => '2026-04-01',
    ]);
    ProjectNote::factory()->create([
        'project_id' => $project->id,
        'title' => 'Kickoff',
        'created_at' => '2026-01-15',
    ]);

    $this->get(route('projects.timeline', $project))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $events = collect(timelineFor($page));

            expect($events->pluck('date')->all())->toBe([
                '2026-04-01', '2026-03-01', '2026-02-15', '2026-02-01', '2026-01-15', '2026-01-01',
            ])->and($events->pluck('kind')->all())->toBe([
                'Security', 'Decision', 'Prototype', 'Vetting', 'Note', 'Project',
            ]);
        });
});

test('work that has finished gets a second entry for the day it did', function () {
    $project = Project::factory()->create(['created_at' => '2026-01-01']);

    VettingItem::factory()->create([
        'project_id' => $project->id,
        'title' => 'Queue the reports',
        'status' => VettingStatus::Vetted,
        'date_raised' => '2026-02-01',
        'date_resolved' => '2026-03-01',
    ]);

    $this->get(route('projects.timeline', $project))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $events = collect(timelineFor($page))->where('kind', 'Vetting')->values();

            expect($events)->toHaveCount(2)
                ->and($events[0]['event'])->toBe('Proposal vetted')
                ->and($events[0]['date'])->toBe('2026-03-01')
                ->and($events[1]['event'])->toBe('Proposal raised')
                ->and($events[1]['date'])->toBe('2026-02-01');
        });
});

test('an abandoned spike says so rather than claiming it finished', function () {
    $project = Project::factory()->create();

    Prototype::factory()->create([
        'project_id' => $project->id,
        'status' => PrototypeStatus::Abandoned,
        'abandoned_reason' => 'Ran out of time.',
        'date_started' => '2026-02-01',
    ]);

    $this->get(route('projects.timeline', $project))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $events = collect(timelineFor($page))->where('kind', 'Prototype')->pluck('event');

            expect($events)->toContain('Spike abandoned')
                ->and($events)->not->toContain('Spike finished');
        });
});

test('a remediated finding shows both the day it was raised and the day it closed', function () {
    $project = Project::factory()->create();

    SecurityNote::factory()->create([
        'project_id' => $project->id,
        'status' => SecurityNoteStatus::Remediated,
        'date_flagged' => '2026-02-01',
        'date_resolved' => '2026-02-20',
    ]);

    $this->get(route('projects.timeline', $project))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $events = collect(timelineFor($page))->where('kind', 'Security')->pluck('event')->all();

            expect($events)->toBe(['Finding remediated', 'Finding flagged']);
        });
});

test('archiving a project closes its timeline', function () {
    $project = Project::factory()->archived()->create();

    $this->get(route('projects.timeline', $project))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            expect(collect(timelineFor($page))->pluck('event'))
                ->toContain('Project archived');
        });
});

test('only this project shows on its timeline', function () {
    $project = Project::factory()->create();
    $other = Project::factory()->create();

    VettingItem::factory()->create(['project_id' => $other->id, 'title' => 'Elsewhere']);
    VettingItem::factory()->create(['title' => 'Unfiled']);

    $this->get(route('projects.timeline', $project))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            expect(collect(timelineFor($page))->pluck('label'))
                ->not->toContain('Elsewhere')
                ->not->toContain('Unfiled');
        });
});

test('a read-only account can read a timeline', function () {
    auth()->logout();
    $this->actingAs(User::factory()->readOnly()->create());

    $project = Project::factory()->create();

    $this->get(route('projects.timeline', $project))->assertOk();
});
