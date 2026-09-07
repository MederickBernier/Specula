<?php

use App\Enums\PrototypeStatus;
use App\Enums\SecurityNoteStatus;
use App\Enums\SecuritySeverity;
use App\Enums\TriageStatus;
use App\Enums\VettingStatus;
use App\Models\FeedSource;
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
            $stats = collect($page->toArray()['props']['stats'])->keyBy('key');

            expect($stats['radar']['value'])->toBe(2)
                ->and($stats['vetting']['value'])->toBe(1)
                ->and($stats['prototypes']['value'])->toBe(2)
                ->and($stats['security']['value'])->toBe(1);
        });
});

test('a deferred finding still counts as open', function () {
    $this->actingAs(User::factory()->create());

    SecurityNote::factory()->deferred()->create();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $props = $page->toArray()['props'];

            expect(collect($props['stats'])->firstWhere('key', 'security')['value'])->toBe(1)
                ->and($props['queues']['deferredFindings'])->toHaveCount(1);
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
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('queues.severeFindings', 2)
            ->where('queues.severeFindings.0.title', 'Critical one')
            ->where('queues.severeFindings.1.title', 'High one'));
});

test('it surfaces proposals waiting on a prototype and feeds that are failing', function () {
    $this->actingAs(User::factory()->create());

    VettingItem::factory()->needsPrototype()->create(['title' => 'Needs a spike']);
    VettingItem::factory()->create(['status' => VettingStatus::InProgress]);

    FeedSource::factory()->create(['last_error' => 'Connection timed out']);
    FeedSource::factory()->create();
    FeedSource::factory()->inactive()->create(['last_error' => 'Gone']);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('queues.needsPrototype', 1)
            ->where('queues.needsPrototype.0.title', 'Needs a spike')
            ->has('queues.staleFeeds', 1)
            ->where('queues.staleFeeds.0.last_error', 'Connection timed out'));
});

test('an abandoned prototype is not counted as running', function () {
    $this->actingAs(User::factory()->create());

    Prototype::factory()->abandoned()->create();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.2.key', 'prototypes')
            ->where('stats.2.value', 0));
});

test('a triaged radar item leaves the queue', function () {
    $this->actingAs(User::factory()->create());

    RadarItem::factory()->create();
    RadarItem::factory()->relevant()->create();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.0.key', 'radar')
            ->where('stats.0.value', 1));

    expect(RadarItem::where('triage_status', TriageStatus::Pending)->count())->toBe(1)
        ->and(SecurityNoteStatus::Deferred->isResolved())->toBeFalse()
        ->and(PrototypeStatus::Abandoned->isFinished())->toBeTrue();
});
