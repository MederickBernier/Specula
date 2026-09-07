<?php

use App\Enums\DecisionStatus;
use App\Enums\SecurityNoteStatus;
use App\Enums\SecuritySeverity;
use App\Models\DecisionRecord;
use App\Models\SecurityNote;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * @return array<string, mixed>
 */
function securityPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Presigned URLs never expire',
        'source' => 'cra_compliance',
        'severity' => SecuritySeverity::High->value,
        'finding' => 'The policy grants write to any signed caller.',
        'is_issue' => true,
        'routed_to' => 'unrouted',
        'status' => SecurityNoteStatus::Flagged->value,
        'date_flagged' => '2026-09-01',
    ], $overrides);
}

test('a decision can be given a date to be looked at again', function () {
    $record = DecisionRecord::factory()->create(['next_review_at' => null]);

    $this->put(route('decisions.update', $record), [
        'project_prefix' => $record->project_prefix,
        'category' => $record->category,
        'sequence' => $record->sequence,
        'title' => $record->title,
        'status' => DecisionStatus::Decided->value,
        'author' => $record->author,
        'proposal_context' => $record->proposal_context,
        'recommendation' => $record->recommendation,
        'conditions_for_revisiting' => 'If the team grows past ten.',
        'next_review_at' => '2027-01-31',
    ])->assertSessionHasNoErrors();

    expect($record->refresh()->next_review_at?->toDateString())->toBe('2027-01-31');
});

test('superseding a decision stops it asking to be revisited', function () {
    $record = DecisionRecord::factory()->create([
        'next_review_at' => now()->addMonth(),
    ]);

    expect($record->next_review_at)->not->toBeNull();

    $record->update(['status' => DecisionStatus::Superseded]);

    expect($record->refresh()->next_review_at)->toBeNull();
});

test('deferring a finding asks when to look at it again', function () {
    $this->post(route('security-notes.store'), securityPayload([
        'status' => SecurityNoteStatus::Deferred->value,
        'deferral_reason' => 'Waiting on the vendor.',
    ]))->assertSessionHasErrors('deferred_until');

    expect(SecurityNote::count())->toBe(0);
});

test('a deferred finding with a date is accepted', function () {
    $this->post(route('security-notes.store'), securityPayload([
        'status' => SecurityNoteStatus::Deferred->value,
        'deferral_reason' => 'Waiting on the vendor.',
        'deferred_until' => '2026-12-01',
    ]))->assertSessionHasNoErrors();

    expect(SecurityNote::sole()->deferred_until?->toDateString())->toBe('2026-12-01');
});

test('a finding that stops being deferred loses its deferral date', function () {
    $note = SecurityNote::factory()->deferred()->create(['deferred_until' => now()->addMonth()]);

    expect($note->deferred_until)->not->toBeNull();

    $note->update(['status' => SecurityNoteStatus::Remediated]);

    expect($note->refresh()->deferred_until)->toBeNull();
});

test('the dashboard gathers everything due, oldest first', function () {
    DecisionRecord::factory()->create([
        'title' => 'Older decision',
        'project_prefix' => 'VNG', 'category' => 'ARCH', 'sequence' => 1,
        'next_review_at' => now()->subWeek(),
    ]);
    SecurityNote::factory()->deferred()->create([
        'title' => 'Elapsed deferral',
        'deferred_until' => now()->subMonth(),
    ]);

    // Not due yet, and so not listed.
    DecisionRecord::factory()->create(['next_review_at' => now()->addMonth()]);
    SecurityNote::factory()->deferred()->create(['deferred_until' => now()->addMonth()]);
    DecisionRecord::factory()->create(['next_review_at' => null]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $props = $page->toArray()['props'];
            $due = $props['queues']['dueForReview'];

            expect($due)->toHaveCount(2)
                ->and($due[0]['kind'])->toBe('Finding')
                ->and($due[0]['label'])->toBe('Elapsed deferral')
                ->and($due[1]['kind'])->toBe('Decision')
                ->and($due[1]['label'])->toContain('VNG-ARCH-001')
                ->and(collect($props['stats'])->firstWhere('key', 'review')['value'])->toBe(2);
        });
});

test('something due today counts as due', function () {
    DecisionRecord::factory()->create(['next_review_at' => now()]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('queues.dueForReview', 1));
});

test('a finding deferred with no date is flagged separately as open-ended', function () {
    SecurityNote::factory()->deferred()->create(['deferred_until' => null, 'title' => 'No date']);
    SecurityNote::factory()->deferred()->create(['deferred_until' => now()->addYear()]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('queues.deferredFindings', 1)
            ->where('queues.deferredFindings.0.title', 'No date'));
});

test('a review date links back to the record it belongs to', function () {
    $record = DecisionRecord::factory()->create(['next_review_at' => now()->subDay()]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('queues.dueForReview.0.url', route('decisions.show', $record)));
});
