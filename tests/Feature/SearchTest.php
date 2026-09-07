<?php

use App\Models\DecisionOption;
use App\Models\DecisionRecord;
use App\Models\Project;
use App\Models\ProjectNote;
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
function groupsFor(AssertableInertia $page): array
{
    return collect($page->toArray()['props']['groups'])->keyBy('module')->all();
}

test('guests cannot search', function () {
    auth()->logout();

    $this->get(route('search', ['q' => 'anything']))->assertRedirect(route('login'));
});

test('an empty search asks for a term rather than listing everything', function () {
    DecisionRecord::factory()->count(3)->create();

    $this->get(route('search'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('search')
            ->where('term', '')
            ->where('total', 0)
            ->has('groups', 0));
});

test('it finds a decision by something written in its body', function () {
    DecisionRecord::factory()->create([
        'title' => 'Pick a frontend stack',
        'recommendation' => 'Go with Inertia because the team already knows React.',
    ]);
    DecisionRecord::factory()->create(['title' => 'Unrelated', 'recommendation' => 'Something else.']);

    $this->get(route('search', ['q' => 'inertia']))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $groups = groupsFor($page);

            expect($groups['Decision records']['total'])->toBe(1)
                ->and($groups['Decision records']['results'][0]['label'])
                ->toContain('Pick a frontend stack')
                ->and($groups['Decision records']['results'][0]['snippet'])
                ->toContain('Inertia');
        });
});

test('it finds a decision by an option weighed under it', function () {
    // The reason options are rows and not a text blob: asking later whether a
    // thing was already weighed somewhere.
    $record = DecisionRecord::factory()->create(['title' => 'How to split the services']);
    DecisionOption::factory()->create([
        'decision_record_id' => $record->id,
        'name' => 'Microservices',
        'cons' => 'Too much for a team this size.',
    ]);

    DecisionRecord::factory()->create(['title' => 'Nothing to do with it']);

    $this->get(route('search', ['q' => 'microservices']))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $groups = groupsFor($page);

            expect($groups['Decision records']['total'])->toBe(1)
                ->and($groups['Decision records']['results'][0]['label'])
                ->toContain('How to split the services');
        });
});

test('it searches every module', function () {
    Project::factory()->create(['name' => 'Postgres migration', 'prefix' => 'PGM']);
    DecisionRecord::factory()->create(['title' => 'Postgres over MySQL']);
    VettingItem::factory()->create(['title' => 'Postgres upgrade']);
    Prototype::factory()->create(['title' => 'Spike', 'hypothesis' => 'Postgres handles it.']);
    SecurityNote::factory()->create(['title' => 'Postgres credentials in logs']);
    ProjectNote::factory()->create(['title' => 'Notes', 'body' => 'Postgres was agreed.']);
    RadarItem::factory()->create(['title' => 'Postgres 18 lands']);

    $this->get(route('search', ['q' => 'postgres']))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $groups = groupsFor($page);

            expect(array_keys($groups))->toBe([
                'Projects',
                'Decision records',
                'Vetting log',
                'Prototypes',
                'Security posture',
                'Project notes',
                'Tech radar',
            ])->and($page->toArray()['props']['total'])->toBe(7);
        });
});

test('modules with no hits are left out entirely', function () {
    DecisionRecord::factory()->create(['title' => 'Only here']);
    VettingItem::factory()->create(['title' => 'Nothing matching']);

    $this->get(route('search', ['q' => 'only here']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('groups', 1));
});

test('search is case-insensitive', function () {
    VettingItem::factory()->create(['title' => 'Kubernetes for the edge boxes']);

    $this->get(route('search', ['q' => 'KUBERNETES']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('total', 1));
});

test('a hit links to the record it found', function () {
    $item = VettingItem::factory()->create(['title' => 'Queue the reporting job']);

    $this->get(route('search', ['q' => 'reporting']))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use ($item) {
            $groups = groupsFor($page);

            expect($groups['Vetting log']['results'][0]['url'])
                ->toBe(route('vetting.show', $item));
        });
});

test('it says how many hits there are beyond the ones it shows', function () {
    VettingItem::factory()->count(12)->create(['title' => 'Queue work']);

    $this->get(route('search', ['q' => 'queue work']))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $groups = groupsFor($page);

            expect($groups['Vetting log']['total'])->toBe(12)
                ->and($groups['Vetting log']['results'])->toHaveCount(8);
        });
});

test('a read-only account can search', function () {
    auth()->logout();
    $this->actingAs(User::factory()->readOnly()->create());

    DecisionRecord::factory()->create(['title' => 'Readable']);

    $this->get(route('search', ['q' => 'readable']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('total', 1));
});
