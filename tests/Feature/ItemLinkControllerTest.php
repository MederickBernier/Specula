<?php

use App\Enums\ItemLinkType;
use App\Models\DecisionRecord;
use App\Models\ItemLink;
use App\Models\Prototype;
use App\Models\SecurityNote;
use App\Models\User;
use App\Models\VettingItem;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests cannot link records', function () {
    auth()->logout();

    $this->post(route('item-links.store'), [])->assertRedirect(route('login'));
});

test('it links a vetting item to the prototype it spawned', function () {
    $vetting = VettingItem::factory()->create();
    $prototype = Prototype::factory()->create();

    $this->from(route('vetting.show', $vetting))
        ->post(route('item-links.store'), [
            'source_type' => 'vetting_item',
            'source_id' => $vetting->id,
            'target_type' => 'prototype',
            'target_id' => $prototype->id,
            'link_type' => ItemLinkType::ResultedIn->value,
            'note' => 'Needed a spike before deciding.',
        ])->assertRedirect(route('vetting.show', $vetting));

    $link = ItemLink::sole();

    expect($link->source->is($vetting))->toBeTrue()
        ->and($link->target->is($prototype))->toBeTrue()
        ->and($link->link_type)->toBe(ItemLinkType::ResultedIn)
        ->and($link->date_linked)->not->toBeNull();
});

test('it rejects a module that is not registered', function () {
    $vetting = VettingItem::factory()->create();

    $this->post(route('item-links.store'), [
        'source_type' => 'vetting_item',
        'source_id' => $vetting->id,
        'target_type' => 'radar_item',
        'target_id' => 1,
        'link_type' => ItemLinkType::RelatedTo->value,
    ])->assertSessionHasErrors('target_type');

    expect(ItemLink::count())->toBe(0);
});

test('it rejects an id that does not exist in the named module', function () {
    $vetting = VettingItem::factory()->create();

    $this->post(route('item-links.store'), [
        'source_type' => 'vetting_item',
        'source_id' => $vetting->id,
        'target_type' => 'prototype',
        'target_id' => 9999,
        'link_type' => ItemLinkType::RelatedTo->value,
    ])->assertSessionHasErrors('target_id');

    expect(ItemLink::count())->toBe(0);
});

test('it rejects a record linked to itself', function () {
    $vetting = VettingItem::factory()->create();

    $this->post(route('item-links.store'), [
        'source_type' => 'vetting_item',
        'source_id' => $vetting->id,
        'target_type' => 'vetting_item',
        'target_id' => $vetting->id,
        'link_type' => ItemLinkType::RelatedTo->value,
    ])->assertSessionHasErrors('target_id');
});

test('it rejects a duplicate link of the same type', function () {
    $vetting = VettingItem::factory()->create();
    $decision = DecisionRecord::factory()->create();

    ItemLink::factory()->between($vetting, $decision)->create([
        'link_type' => ItemLinkType::ResultedIn,
    ]);

    $this->post(route('item-links.store'), [
        'source_type' => 'vetting_item',
        'source_id' => $vetting->id,
        'target_type' => 'decision_record',
        'target_id' => $decision->id,
        'link_type' => ItemLinkType::ResultedIn->value,
    ])->assertSessionHasErrors('link_type');

    expect(ItemLink::count())->toBe(1);
});

test('the same pair can hold two different link types', function () {
    $vetting = VettingItem::factory()->create();
    $decision = DecisionRecord::factory()->create();

    ItemLink::factory()->between($vetting, $decision)->create([
        'link_type' => ItemLinkType::ResultedIn,
    ]);

    $this->post(route('item-links.store'), [
        'source_type' => 'vetting_item',
        'source_id' => $vetting->id,
        'target_type' => 'decision_record',
        'target_id' => $decision->id,
        'link_type' => ItemLinkType::RelatedTo->value,
    ])->assertSessionHasNoErrors();

    expect(ItemLink::count())->toBe(2);
});

test('it removes a link', function () {
    $link = ItemLink::factory()->create();

    $this->delete(route('item-links.destroy', $link))->assertRedirect();

    expect(ItemLink::count())->toBe(0);
});

test('a show page renders both link directions with somewhere to click through to', function () {
    $vetting = VettingItem::factory()->create();
    $prototype = Prototype::factory()->create();
    $security = SecurityNote::factory()->create();

    ItemLink::factory()->between($vetting, $prototype)->create();
    ItemLink::factory()->between($security, $vetting)->create();

    $this->get(route('vetting.show', $vetting))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('itemLinks.outgoing', 1)
            ->has('itemLinks.incoming', 1)
            ->where('itemLinks.outgoing.0.other.module', 'Prototype')
            ->where('itemLinks.outgoing.0.other.label', $prototype->title)
            ->where('itemLinks.outgoing.0.other.url', route('prototypes.show', $prototype))
            ->where('itemLinks.incoming.0.other.module', 'Security note')
            ->where('itemLinkSource.type', 'vetting_item'));
});

test('a show page offers every other record as a link target', function () {
    $vetting = VettingItem::factory()->create();
    VettingItem::factory()->create();
    Prototype::factory()->count(2)->create();
    DecisionRecord::factory()->create();

    $this->get(route('vetting.show', $vetting))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $groups = collect($page->toArray()['props']['itemLinkTargets']);

            expect($groups->pluck('type')->all())
                ->toBe(['decision_record', 'vetting_item', 'prototype'])
                ->and($groups->firstWhere('type', 'vetting_item')['records'])->toHaveCount(1)
                ->and($groups->firstWhere('type', 'prototype')['records'])->toHaveCount(2);
        });
});

test('decision records label themselves by document id in a link table', function () {
    $decision = DecisionRecord::factory()->create([
        'project_prefix' => 'VNG', 'category' => 'ARCH', 'sequence' => 3,
    ]);

    expect($decision->linkLabel())->toStartWith('VNG-ARCH-003 — ');
});
