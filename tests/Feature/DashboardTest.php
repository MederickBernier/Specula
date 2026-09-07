<?php

use App\Enums\PrototypeStatus;
use App\Enums\SecurityNoteStatus;
use App\Enums\SecuritySeverity;
use App\Enums\TriageStatus;
use App\Enums\VettingStatus;
use App\Models\DecisionRecord;
use App\Models\FeedSource;
use App\Models\Project;
use App\Models\Prototype;
use App\Models\RadarItem;
use App\Models\SecurityNote;
use App\Models\User;
use App\Models\VettingItem;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('it counts open work rather than totals', function () {
    $this->actingAs(User::factory()->create());

    RadarItem::factory()->count(2)->create();
    RadarItem::factory()->discarded()->create();

    VettingItem::factory()->create();
    VettingItem::factory()->vetted()->create();

    Prototype::factory()->create();
    Prototype::factory()->inProgress()->create();
    Prototype::factory()->completed()->create();

    SecurityNote::factory()->create();
    SecurityNote::factory()->remediated()->create();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $counts = collect($page->toArray()['props']['counts'])->keyBy('key');

            expect($counts['radar']['value'])->toBe(2)
                ->and($counts['vetting']['value'])->toBe(1)
                ->and($counts['prototypes']['value'])->toBe(2)
                ->and($counts['security']['value'])->toBe(1);
        });
});

test('a deferred finding still counts as open', function () {
    $this->actingAs(User::factory()->create());

    SecurityNote::factory()->deferred()->create();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $props = $page->toArray()['props'];

            expect(collect($props['counts'])->firstWhere('key', 'security')['value'])->toBe(1)
                // a deferral with no date is one of the things asking for
                // attention, whether or not it is also severe
                ->and(collect($props['attention'])->contains(
                    fn (array $item): bool => str_contains(
                        $item['why'],
                        'Deferred with no date to come back to',
                    ),
                ))->toBeTrue();
        });
});

test('it lists the severe findings critical first', function () {
    $this->actingAs(User::factory()->create());

    SecurityNote::factory()->create(['title' => 'High one', 'severity' => SecuritySeverity::High]);
    SecurityNote::factory()->create(['title' => 'Critical one', 'severity' => SecuritySeverity::Critical]);
    SecurityNote::factory()->create(['title' => 'Low one', 'severity' => SecuritySeverity::Low]);
    SecurityNote::factory()->nonIssue()->create([
        'title' => 'Not real', 'severity' => SecuritySeverity::Critical,
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $findings = collect($page->toArray()['props']['attention'])
                ->where('kind', 'Finding')
                ->values();

            expect($findings)->toHaveCount(2)
                ->and($findings[0]['label'])->toBe('Critical one')
                ->and($findings[1]['label'])->toBe('High one');
        });
});

test('it surfaces proposals waiting on a prototype and feeds that are failing', function () {
    $this->actingAs(User::factory()->create());

    VettingItem::factory()->needsPrototype()->create(['title' => 'Needs a spike']);
    VettingItem::factory()->create(['status' => VettingStatus::InProgress]);

    FeedSource::factory()->create(['name' => 'A failing feed', 'last_error' => 'Connection timed out']);
    FeedSource::factory()->create();
    FeedSource::factory()->inactive()->create(['last_error' => 'Gone']);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $attention = collect($page->toArray()['props']['attention']);

            expect($attention->where('kind', 'Proposal')->pluck('label')->all())
                ->toBe(['Needs a spike'])
                ->and($attention->where('kind', 'Feed')->pluck('label')->all())
                ->toBe(['A failing feed']);
        });
});

test('an abandoned prototype is not counted as running', function () {
    $this->actingAs(User::factory()->create());

    Prototype::factory()->abandoned()->create();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('counts.2.key', 'prototypes')
            ->where('counts.2.value', 0));
});

test('a triaged radar item leaves the queue', function () {
    $this->actingAs(User::factory()->create());

    RadarItem::factory()->create();
    RadarItem::factory()->relevant()->create();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('counts.0.key', 'radar')
            ->where('counts.0.value', 1));

    expect(RadarItem::where('triage_status', TriageStatus::Pending)->count())->toBe(1)
        ->and(SecurityNoteStatus::Deferred->isResolved())->toBeFalse()
        ->and(PrototypeStatus::Abandoned->isFinished())->toBeTrue();
});

test('one record with several reasons is one row, carrying both', function () {
    $this->actingAs(User::factory()->create());

    SecurityNote::factory()->create([
        'title' => 'Presigned URLs',
        'severity' => SecuritySeverity::Critical,
        'status' => SecurityNoteStatus::Deferred,
        'deferral_reason' => 'Waiting on the vendor.',
        'deferred_until' => now()->subWeek(),
        'date_flagged' => now()->subMonths(2),
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $rows = collect($page->toArray()['props']['attention'])
                ->where('label', 'Presigned URLs')
                ->values();

            expect($rows)->toHaveCount(1)
                ->and($rows[0]['why'])->toContain('Critical')
                ->and($rows[0]['why'])->toContain('which has passed');
        });
});

test('a quiet instance says so rather than showing empty cards', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('attention', 0)
            ->has('activity', 0)
            ->has('projects', 0));
});

test('the activity feed reads across modules, newest first', function () {
    $this->actingAs(User::factory()->create());

    $decision = DecisionRecord::factory()->create(['title' => 'Older decision']);
    $decision->timestamps = false;
    $decision->updated_at = now()->subDays(3);
    $decision->save();

    VettingItem::factory()->create(['title' => 'Newer proposal']);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $activity = collect($page->toArray()['props']['activity']);

            expect($activity->pluck('kind')->all())->toBe(['Vetting', 'Decision'])
                ->and($activity->first()['label'])->toBe('Newer proposal')
                ->and($activity->first()['state'])->not->toBeNull();
        });
});

test('projects show what is still moving in each', function () {
    $this->actingAs(User::factory()->create());

    $project = Project::factory()->create(['name' => 'Vision', 'prefix' => 'VNG']);
    VettingItem::factory()->count(2)->create(['project_id' => $project->id]);
    Prototype::factory()->inProgress()->create(['project_id' => $project->id]);

    // Archived projects are not what is moving.
    Project::factory()->archived()->create();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $projects = collect($page->toArray()['props']['projects']);

            expect($projects)->toHaveCount(1)
                ->and($projects[0]['prefix'])->toBe('VNG')
                ->and(collect($projects[0]['open'])->firstWhere('label', 'proposals')['value'])
                ->toBe(2)
                // one spike reads as a spike, not "1 spikes"
                ->and(collect($projects[0]['open'])->firstWhere('label', 'spike')['value'])
                ->toBe(1);
        });
});
