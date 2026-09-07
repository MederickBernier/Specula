<?php

use App\Enums\VettingSourceType;
use App\Enums\VettingStatus;
use App\Models\User;
use App\Models\VettingItem;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * @return array<string, mixed>
 */
function vettingPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Move the reporting job to a queue',
        'source_type' => VettingSourceType::Meeting->value,
        'source_detail' => 'Monday architecture sync',
        'date_raised' => '2026-09-01',
        'proposal_description' => 'Run it **async** instead of in the request.',
        'assessment' => null,
        'status' => VettingStatus::New->value,
        'rejection_reason' => null,
        'external_url' => null,
    ], $overrides);
}

test('guests cannot reach the vetting log', function () {
    auth()->logout();

    $this->get(route('vetting.index'))->assertRedirect(route('login'));
    $this->get(route('vetting.create'))->assertRedirect(route('login'));
    $this->post(route('vetting.store'), [])->assertRedirect(route('login'));
});

test('the index lists items newest raised first', function () {
    VettingItem::factory()->create(['title' => 'Older', 'date_raised' => '2026-01-01']);
    VettingItem::factory()->create(['title' => 'Newer', 'date_raised' => '2026-06-01']);

    $this->get(route('vetting.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('vetting/index')
            ->has('items', 2)
            ->where('items.0.title', 'Newer')
            ->where('items.1.title', 'Older')
            ->has('statuses', 5)
            ->has('sourceTypes', 4));
});

test('it stores an item', function () {
    $this->post(route('vetting.store'), vettingPayload())
        ->assertRedirect(route('vetting.show', VettingItem::first()));

    $item = VettingItem::first();

    expect($item->title)->toBe('Move the reporting job to a queue')
        ->and($item->source_type)->toBe(VettingSourceType::Meeting)
        ->and($item->status)->toBe(VettingStatus::New)
        ->and($item->date_resolved)->toBeNull();
});

test('it requires a rejection reason when the status is rejected', function () {
    $this->post(route('vetting.store'), vettingPayload([
        'status' => VettingStatus::Rejected->value,
    ]))->assertSessionHasErrors('rejection_reason');

    expect(VettingItem::count())->toBe(0);
});

test('it rejects a malformed external url', function () {
    $this->post(route('vetting.store'), vettingPayload(['external_url' => 'not a url']))
        ->assertSessionHasErrors('external_url');
});

test('it stamps the resolution date when an item is resolved', function () {
    $item = VettingItem::factory()->create();

    expect($item->date_resolved)->toBeNull();

    $this->put(route('vetting.update', $item), vettingPayload([
        'status' => VettingStatus::Vetted->value,
        'assessment' => 'Worth doing.',
    ]))->assertRedirect(route('vetting.show', $item));

    expect($item->refresh()->date_resolved)->not->toBeNull();
});

test('it clears the resolution date when an item is reopened', function () {
    $item = VettingItem::factory()->vetted()->create();

    expect($item->date_resolved)->not->toBeNull();

    $this->put(route('vetting.update', $item), vettingPayload([
        'status' => VettingStatus::InProgress->value,
    ]));

    expect($item->refresh()->date_resolved)->toBeNull();
});

test('an item needing a prototype is not treated as resolved', function () {
    $item = VettingItem::factory()->needsPrototype()->create();

    expect($item->status->isResolved())->toBeFalse()
        ->and($item->date_resolved)->toBeNull();
});

test('it keeps a resolution date that is already set', function () {
    $item = VettingItem::factory()->rejected()->create();
    $resolvedOn = $item->date_resolved;

    $item->update(['title' => 'Renamed']);

    expect($item->refresh()->date_resolved->toDateString())->toBe($resolvedOn->toDateString());
});

test('it deletes an item', function () {
    $item = VettingItem::factory()->create();

    $this->delete(route('vetting.destroy', $item))
        ->assertRedirect(route('vetting.index'));

    expect(VettingItem::count())->toBe(0);
});

test('the show page renders markdown and strips raw html', function () {
    $item = VettingItem::factory()->create([
        'proposal_description' => "Run it **async**.\n\n<script>alert('x')</script>",
        'assessment' => null,
    ]);

    $this->get(route('vetting.show', $item))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('vetting/show')
            ->where('html.proposal_description', fn (string $html) => str_contains($html, '<strong>async</strong>')
                && ! str_contains($html, '<script>'))
            ->where('html.assessment', null));
});
