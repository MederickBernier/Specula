<?php

use App\Models\FeedSource;
use App\Models\SavedSearch;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('guests cannot save a search', function () {
    auth()->logout();

    $this->post(route('radar.saved-searches.store'), ['name' => 'Postgres'])
        ->assertRedirect(route('login'));
});

test('it saves the current filters under a name', function () {
    $feed = FeedSource::factory()->create();

    $this->from(route('radar.index'))
        ->post(route('radar.saved-searches.store'), [
            'name' => 'Postgres watch',
            'q' => 'postgres',
            'feed' => $feed->id,
            'status' => 'pending',
        ])->assertRedirect(route('radar.index'));

    $saved = SavedSearch::sole();

    expect($saved->name)->toBe('Postgres watch')
        ->and($saved->user_id)->toBe($this->user->id)
        ->and($saved->filters)->toBe([
            'q' => 'postgres',
            'feed' => (string) $feed->id,
            'status' => 'pending',
        ]);
});

test('it drops the filters that narrow nothing', function () {
    $this->post(route('radar.saved-searches.store'), [
        'name' => 'Just text',
        'q' => 'postgres',
        'feed' => null,
        'status' => null,
    ]);

    expect(SavedSearch::sole()->filters)->toBe(['q' => 'postgres']);
});

test('it refuses to save a search that narrows nothing', function () {
    $this->post(route('radar.saved-searches.store'), ['name' => 'Everything'])
        ->assertRedirect();

    expect(SavedSearch::count())->toBe(0);
});

test('names are unique per person, not globally', function () {
    SavedSearch::factory()->create(['user_id' => $this->user->id, 'name' => 'Postgres']);

    $this->post(route('radar.saved-searches.store'), ['name' => 'Postgres', 'q' => 'pg'])
        ->assertSessionHasErrors('name');

    // Someone else may use the same name for their own search.
    $other = User::factory()->create();
    SavedSearch::factory()->create(['user_id' => $other->id, 'name' => 'Postgres']);

    expect(SavedSearch::where('name', 'Postgres')->count())->toBe(2);
});

test('it rejects a feed that does not exist and a status that is not real', function () {
    $this->post(route('radar.saved-searches.store'), [
        'name' => 'Bad feed', 'q' => 'x', 'feed' => 9999,
    ])->assertSessionHasErrors('feed');

    $this->post(route('radar.saved-searches.store'), [
        'name' => 'Bad status', 'q' => 'x', 'status' => 'nonsense',
    ])->assertSessionHasErrors('status');

    expect(SavedSearch::count())->toBe(0);
});

test('the radar page only offers your own saved searches', function () {
    SavedSearch::factory()->create(['user_id' => $this->user->id, 'name' => 'Mine']);
    SavedSearch::factory()->create(['user_id' => User::factory()->create()->id, 'name' => 'Theirs']);

    $this->get(route('radar.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('savedSearches', 1)
            ->where('savedSearches.0.name', 'Mine'));
});

test('you can forget your own saved search', function () {
    $saved = SavedSearch::factory()->create(['user_id' => $this->user->id]);

    $this->from(route('radar.index'))
        ->delete(route('radar.saved-searches.destroy', $saved))
        ->assertRedirect(route('radar.index'));

    expect(SavedSearch::count())->toBe(0);
});

test('you cannot forget someone else\'s', function () {
    $saved = SavedSearch::factory()->create([
        'user_id' => User::factory()->create()->id,
    ]);

    $this->delete(route('radar.saved-searches.destroy', $saved))->assertForbidden();

    expect(SavedSearch::count())->toBe(1);
});

test('a read-only account keeps its own shortcuts', function () {
    auth()->logout();
    $reader = User::factory()->readOnly()->create();
    $this->actingAs($reader);

    $this->post(route('radar.saved-searches.store'), ['name' => 'CVEs', 'q' => 'cve'])
        ->assertRedirect();

    $saved = SavedSearch::sole();

    expect($saved->user_id)->toBe($reader->id);

    $this->delete(route('radar.saved-searches.destroy', $saved))->assertRedirect();

    expect(SavedSearch::count())->toBe(0);
});

test('deleting an account takes its saved searches with it', function () {
    SavedSearch::factory()->count(2)->create(['user_id' => $this->user->id]);

    $this->user->delete();

    expect(SavedSearch::count())->toBe(0);
});
