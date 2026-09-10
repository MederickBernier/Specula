<?php

use App\Models\DecisionRecord;
use App\Models\FeedSource;
use App\Models\ItemLink;
use App\Models\Prototype;
use App\Models\RadarItem;
use App\Models\SecurityNote;
use App\Models\User;
use App\Models\VettingItem;

test('a read-only account can read every module', function () {
    $this->actingAs(User::factory()->readOnly()->create());

    $this->get(route('dashboard'))->assertOk();
    $this->get(route('decisions.index'))->assertOk();
    $this->get(route('vetting.index'))->assertOk();
    $this->get(route('prototypes.index'))->assertOk();
    $this->get(route('security-notes.index'))->assertOk();
    $this->get(route('radar.index'))->assertOk();
    $this->get(route('radar.feeds.index'))->assertOk();

    $this->get(route('decisions.show', DecisionRecord::factory()->create()))->assertOk();
    $this->get(route('vetting.show', VettingItem::factory()->create()))->assertOk();
});

test('a read-only account cannot create anything', function () {
    $this->actingAs(User::factory()->readOnly()->create());

    $this->post(route('decisions.store'), [])->assertForbidden();
    $this->post(route('vetting.store'), [])->assertForbidden();
    $this->post(route('prototypes.store'), [])->assertForbidden();
    $this->post(route('security-notes.store'), [])->assertForbidden();
    $this->post(route('item-links.store'), [])->assertForbidden();
    $this->post(route('radar.feeds.store'), [])->assertForbidden();

    expect(DecisionRecord::count())->toBe(0)
        ->and(VettingItem::count())->toBe(0)
        ->and(Prototype::count())->toBe(0)
        ->and(SecurityNote::count())->toBe(0);
});

test('a read-only account cannot update or delete anything', function () {
    $this->actingAs(User::factory()->readOnly()->create());

    $decision = DecisionRecord::factory()->create();
    $vetting = VettingItem::factory()->create(['title' => 'Untouched']);
    $prototype = Prototype::factory()->create();
    $note = SecurityNote::factory()->create();
    $radarItem = RadarItem::factory()->create();
    $feed = FeedSource::factory()->create();

    $this->put(route('decisions.update', $decision), [])->assertForbidden();
    $this->put(route('vetting.update', $vetting), ['title' => 'Changed'])->assertForbidden();
    $this->put(route('prototypes.update', $prototype), [])->assertForbidden();
    $this->put(route('security-notes.update', $note), [])->assertForbidden();
    $this->patch(route('radar.triage', $radarItem), [])->assertForbidden();
    $this->post(route('radar.feeds.fetch', $feed), [])->assertForbidden();

    $this->delete(route('decisions.destroy', $decision))->assertForbidden();
    $this->delete(route('vetting.destroy', $vetting))->assertForbidden();
    $this->delete(route('prototypes.destroy', $prototype))->assertForbidden();
    $this->delete(route('security-notes.destroy', $note))->assertForbidden();

    expect($vetting->refresh()->title)->toBe('Untouched')
        ->and(DecisionRecord::count())->toBe(1)
        ->and(VettingItem::count())->toBe(1)
        ->and(Prototype::count())->toBe(1)
        ->and(SecurityNote::count())->toBe(1);
});

test('a read-only account cannot remove a cross-module link', function () {
    $this->actingAs(User::factory()->readOnly()->create());

    $link = ItemLink::factory()->create();

    $this->delete(route('item-links.destroy', $link))->assertForbidden();

    expect(ItemLink::count())->toBe(1);
});

test('a full account is unaffected by the guard', function () {
    $this->actingAs(User::factory()->create());

    $vetting = VettingItem::factory()->create();

    $this->delete(route('vetting.destroy', $vetting))->assertRedirect(route('vetting.index'));

    expect(VettingItem::count())->toBe(0);
});

test('a read-only account can still manage its own login', function () {
    $user = User::factory()->readOnly()->create();
    $this->actingAs($user);

    $this->get(route('profile.edit'))->assertOk();
    $this->patch(route('profile.update'), [
        'name' => 'Renamed',
        'email' => $user->email,
    ])->assertRedirect(route('profile.edit'));

    expect($user->refresh()->name)->toBe('Renamed');

    $this->post(route('logout'))->assertRedirect();
    $this->assertGuest();
});

test('the frontend is told whether the account may write', function () {
    $this->actingAs(User::factory()->readOnly()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.canWrite', false)
            ->where('auth.isAdmin', false));

    auth()->logout();
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.canWrite', true)
            ->where('auth.isAdmin', true));
});

test('the console command can create a read-only account', function () {
    $this->artisan('clearsight:create-user --name="Reader" --email=reader@example.com --read-only', [
    ])->expectsQuestion('Password', 'Str0ng-Password!')->assertSuccessful();

    $user = User::where('email', 'reader@example.com')->sole();

    expect($user->is_read_only)->toBeTrue()
        ->and($user->is_admin)->toBeFalse()
        ->and($user->canWrite())->toBeFalse();
});
