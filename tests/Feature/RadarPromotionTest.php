<?php

use App\Enums\ItemLinkType;
use App\Enums\TriageStatus;
use App\Enums\VettingSourceType;
use App\Enums\VettingStatus;
use App\Models\FeedSource;
use App\Models\ItemLink;
use App\Models\RadarItem;
use App\Models\User;
use App\Models\VettingItem;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('promoting a radar item raises a linked vetting item', function () {
    $feed = FeedSource::factory()->create(['name' => 'Laravel News']);
    $item = RadarItem::factory()->create([
        'feed_source_id' => $feed->id,
        'title' => 'Queues get a new driver',
        'url' => 'https://example.test/queues',
        'summary' => 'The driver batches jobs before dispatching them.',
    ]);

    $this->post(route('radar.promote', $item))
        ->assertRedirect(route('vetting.edit', VettingItem::sole()));

    $vetting = VettingItem::sole();

    expect($vetting->title)->toBe('Queues get a new driver')
        ->and($vetting->source_type)->toBe(VettingSourceType::TechRadar)
        ->and($vetting->source_detail)->toBe('Laravel News')
        ->and($vetting->status)->toBe(VettingStatus::New)
        ->and($vetting->external_url)->toBe('https://example.test/queues')
        ->and($vetting->proposal_description)
        ->toContain('The driver batches jobs before dispatching them.')
        ->toContain('https://example.test/queues');

    $link = ItemLink::sole();

    expect($link->source->is($item))->toBeTrue()
        ->and($link->target->is($vetting))->toBeTrue()
        ->and($link->link_type)->toBe(ItemLinkType::ResultedIn);
});

test('promoting an untriaged item takes it out of the queue', function () {
    $item = RadarItem::factory()->create();

    $this->post(route('radar.promote', $item));

    $item->refresh();

    expect($item->triage_status)->toBe(TriageStatus::Relevant)
        ->and($item->relevance_note)->not->toBeNull()
        ->and($item->is_hidden)->toBeFalse();
});

test('promoting keeps a relevance note that was already written', function () {
    $item = RadarItem::factory()->relevant()->create(['relevance_note' => 'Matters for VNG.']);

    $this->post(route('radar.promote', $item));

    expect($item->refresh()->relevance_note)->toBe('Matters for VNG.');
});

test('an item is not promoted twice', function () {
    $item = RadarItem::factory()->create();

    $this->post(route('radar.promote', $item));
    $this->post(route('radar.promote', $item))->assertRedirect();

    expect(VettingItem::count())->toBe(1)
        ->and(ItemLink::count())->toBe(1);
});

test('the queue says which items were already promoted', function () {
    $promoted = RadarItem::factory()->create();
    RadarItem::factory()->create();

    $this->post(route('radar.promote', $promoted));

    $this->get(route('radar.index'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use ($promoted) {
            $items = collect($page->toArray()['props']['items'])->keyBy('id');

            expect($items[$promoted->id]['promoted'])->toBeTrue()
                ->and($items->firstWhere('id', '!=', $promoted->id)['promoted'])->toBeFalse();
        });
});

test('a read-only account cannot promote', function () {
    auth()->logout();
    $this->actingAs(User::factory()->readOnly()->create());

    $item = RadarItem::factory()->create();

    $this->post(route('radar.promote', $item))->assertForbidden();

    expect(VettingItem::count())->toBe(0);
});
