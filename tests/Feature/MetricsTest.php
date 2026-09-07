<?php

use App\Enums\SecuritySeverity;
use App\Models\DecisionRecord;
use App\Models\Prototype;
use App\Models\RadarItem;
use App\Models\SecurityNote;
use App\Models\User;
use App\Models\VettingItem;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * @return array<string, mixed>
 */
function metricsFor(AssertableInertia $page): array
{
    return $page->toArray()['props']['metrics'];
}

test('guests cannot see the metrics', function () {
    auth()->logout();

    $this->get(route('metrics'))->assertRedirect(route('login'));
});

test('an empty instance reports nothing rather than dividing by zero', function () {
    $this->get(route('metrics'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $page->component('metrics');

            $m = metricsFor($page);

            expect($m['decisions']['total'])->toBe(0)
                ->and($m['decisions']['settledShare'])->toBeNull()
                ->and($m['vetting']['medianDaysToResolve'])->toBeNull()
                ->and($m['prototypes']['abandonedShare'])->toBeNull()
                ->and($m['radar']['triagedShare'])->toBeNull();
        });
});

test('it says how much of the decision log is actually settled', function () {
    DecisionRecord::factory()->count(3)->create();
    DecisionRecord::factory()->decided()->create();
    DecisionRecord::factory()->superseded()->create();

    $this->get(route('metrics'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $decisions = metricsFor($page)['decisions'];

            expect($decisions['total'])->toBe(5)
                ->and($decisions['settledShare'])->toBe(40)
                ->and(collect($decisions['split'])->firstWhere('label', 'Draft')['value'])->toBe(3);
        });
});

test('it counts decisions into the quarter they were recorded in', function () {
    DecisionRecord::factory()->create(['created_at' => now()]);
    DecisionRecord::factory()->create(['created_at' => now()]);
    DecisionRecord::factory()->create(['created_at' => now()->subYears(5)]);

    $this->get(route('metrics'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $quarters = collect(metricsFor($page)['decisions']['perQuarter']);

            expect($quarters)->toHaveCount(8)
                // the current quarter is the last of the eight
                ->and($quarters->last()['value'])->toBe(2)
                // anything older than the window is simply not shown
                ->and($quarters->sum('value'))->toBe(2);
        });
});

test('it reports the middle time to resolve a proposal, not the average', function () {
    // Spans of 1, 2 and 30 days: the median ignores the one that was forgotten.
    VettingItem::factory()->vetted()->create(['date_raised' => '2026-01-01', 'date_resolved' => '2026-01-02']);
    VettingItem::factory()->vetted()->create(['date_raised' => '2026-01-01', 'date_resolved' => '2026-01-03']);
    VettingItem::factory()->vetted()->create(['date_raised' => '2026-01-01', 'date_resolved' => '2026-01-31']);

    $this->get(route('metrics'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => expect(
            metricsFor($page)['vetting']['medianDaysToResolve']
        )->toBe(2));
});

test('shares are counted against what has actually finished', function () {
    Prototype::factory()->completed()->create();
    Prototype::factory()->abandoned()->create();
    // Still running, so it is in neither share.
    Prototype::factory()->inProgress()->create();

    $this->get(route('metrics'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $prototypes = metricsFor($page)['prototypes'];

            expect($prototypes['total'])->toBe(3)
                ->and($prototypes['abandonedShare'])->toBe(50)
                ->and(collect($prototypes['split'])->firstWhere('label', 'Abandoned')['value'])->toBe(1);
        });
});

test('it measures the triage call on findings', function () {
    SecurityNote::factory()->create(['severity' => SecuritySeverity::High]);
    SecurityNote::factory()->nonIssue()->create();
    SecurityNote::factory()->remediated()->create([
        'date_flagged' => '2026-01-01',
        'date_resolved' => '2026-01-11',
    ]);

    $this->get(route('metrics'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $security = metricsFor($page)['security'];

            expect($security['total'])->toBe(3)
                ->and($security['open'])->toBe(1)
                ->and($security['nonIssueShare'])->toBe(33)
                ->and($security['medianDaysToRemediate'])->toBe(10);
        });
});

test('it says whether the feeds are actually being read', function () {
    RadarItem::factory()->count(2)->create();
    RadarItem::factory()->relevant()->create();
    RadarItem::factory()->discarded()->create();

    $this->get(route('metrics'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $radar = metricsFor($page)['radar'];

            expect($radar['total'])->toBe(4)
                ->and($radar['triagedShare'])->toBe(50)
                ->and($radar['relevantShare'])->toBe(50)
                ->and($radar['becameWork'])->toBe(0);
        });
});

test('it counts a radar item that became work once, however many links it has', function () {
    $item = RadarItem::factory()->create();

    $this->post(route('radar.promote', $item), ['target' => 'vetting']);
    $this->post(route('radar.promote', $item), ['target' => 'prototype']);

    $this->get(route('metrics'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => expect(
            metricsFor($page)['radar']['becameWork']
        )->toBe(1));
});

test('a read-only account can see the metrics', function () {
    auth()->logout();
    $this->actingAs(User::factory()->readOnly()->create());

    $this->get(route('metrics'))->assertOk();
});
