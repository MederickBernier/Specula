<?php

use App\Enums\SecurityNoteSource;
use App\Enums\SecurityNoteStatus;
use App\Enums\SecurityRoutedTo;
use App\Enums\SecuritySeverity;
use App\Models\SecurityNote;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * @return array<string, mixed>
 */
function securityNotePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Presigned upload URLs never expire',
        'source' => SecurityNoteSource::CRACompliance->value,
        'category' => 'IAM',
        'severity' => SecuritySeverity::High->value,
        'finding' => 'The bucket policy grants **write** to any signed caller.',
        'is_issue' => true,
        'non_issue_reason' => null,
        'routed_to' => SecurityRoutedTo::Unrouted->value,
        'status' => SecurityNoteStatus::Flagged->value,
        'deferral_reason' => null,
        'date_flagged' => '2026-09-01',
        'external_url' => null,
    ], $overrides);
}

test('guests cannot reach the security notes', function () {
    auth()->logout();

    $this->get(route('security-notes.index'))->assertRedirect(route('login'));
    $this->get(route('security-notes.create'))->assertRedirect(route('login'));
    $this->post(route('security-notes.store'), [])->assertRedirect(route('login'));
});

test('the account security page keeps its own route name', function () {
    expect(route('security.edit'))->toContain('settings/security')
        ->and(route('security-notes.index'))->toContain('security-notes');
});

test('the index lists notes newest flagged first', function () {
    SecurityNote::factory()->create(['title' => 'Older', 'date_flagged' => '2026-01-01']);
    SecurityNote::factory()->create(['title' => 'Newer', 'date_flagged' => '2026-06-01']);

    $this->get(route('security-notes.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('security/index')
            ->has('notes', 2)
            ->where('notes.0.title', 'Newer')
            ->has('sources', 6)
            ->has('severities', 4)
            ->has('routes', 4)
            ->has('statuses', 5));
});

test('it stores a flagged finding', function () {
    $this->post(route('security-notes.store'), securityNotePayload())
        ->assertRedirect(route('security-notes.show', SecurityNote::first()));

    $note = SecurityNote::first();

    expect($note->severity)->toBe(SecuritySeverity::High)
        ->and($note->is_issue)->toBeTrue()
        ->and($note->date_resolved)->toBeNull();
});

test('a note that is not an issue needs a reason', function () {
    $this->post(route('security-notes.store'), securityNotePayload([
        'is_issue' => false,
        'status' => SecurityNoteStatus::NonIssue->value,
    ]))->assertSessionHasErrors('non_issue_reason');

    expect(SecurityNote::count())->toBe(0);
});

test('a deferred finding needs a deferral reason', function () {
    $this->post(route('security-notes.store'), securityNotePayload([
        'status' => SecurityNoteStatus::Deferred->value,
    ]))->assertSessionHasErrors('deferral_reason');
});

test('a note closed as a non-issue cannot also be a real issue', function () {
    $this->post(route('security-notes.store'), securityNotePayload([
        'is_issue' => true,
        'status' => SecurityNoteStatus::NonIssue->value,
    ]))->assertSessionHasErrors('status');

    expect(SecurityNote::count())->toBe(0);
});

test('a note that is not an issue cannot stay open under another status', function () {
    $this->post(route('security-notes.store'), securityNotePayload([
        'is_issue' => false,
        'non_issue_reason' => 'The endpoint is not reachable from outside the VPC.',
        'status' => SecurityNoteStatus::Routed->value,
    ]))->assertSessionHasErrors('status');
});

test('it accepts a consistent non-issue', function () {
    $this->post(route('security-notes.store'), securityNotePayload([
        'is_issue' => false,
        'non_issue_reason' => 'The endpoint is not reachable from outside the VPC.',
        'status' => SecurityNoteStatus::NonIssue->value,
    ]))->assertSessionHasNoErrors();

    expect(SecurityNote::first()->date_resolved)->not->toBeNull();
});

test('it stamps the resolution date when a finding is remediated', function () {
    $note = SecurityNote::factory()->routed()->create();

    expect($note->date_resolved)->toBeNull();

    $this->put(route('security-notes.update', $note), securityNotePayload([
        'status' => SecurityNoteStatus::Remediated->value,
        'routed_to' => SecurityRoutedTo::SelfHandled->value,
    ]))->assertRedirect(route('security-notes.show', $note));

    expect($note->refresh()->date_resolved)->not->toBeNull();
});

test('a deferred finding stays open', function () {
    $note = SecurityNote::factory()->deferred()->create();

    expect($note->status->isResolved())->toBeFalse()
        ->and($note->date_resolved)->toBeNull();
});

test('it rejects a malformed external url', function () {
    $this->post(route('security-notes.store'), securityNotePayload(['external_url' => 'not a url']))
        ->assertSessionHasErrors('external_url');
});

test('it deletes a note', function () {
    $note = SecurityNote::factory()->create();

    $this->delete(route('security-notes.destroy', $note))
        ->assertRedirect(route('security-notes.index'));

    expect(SecurityNote::count())->toBe(0);
});

test('the show page renders markdown and strips raw html', function () {
    $note = SecurityNote::factory()->create([
        'finding' => "The policy grants **write** access.\n\n<script>alert('x')</script>",
    ]);

    $this->get(route('security-notes.show', $note))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('security/show')
            ->where('html.finding', fn (string $html) => str_contains($html, '<strong>write</strong>')
                && ! str_contains($html, '<script>'))
            ->where('html.deferral_reason', null));
});
