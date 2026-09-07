<?php

use App\Enums\TechnologyCategory;
use App\Enums\TechnologyRing;
use App\Enums\TechnologyStatus;
use App\Models\DecisionRecord;
use App\Models\Project;
use App\Models\Prototype;
use App\Models\SecurityNote;
use App\Models\Technology;
use App\Models\TechnologyUsage;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * @return array<string, mixed>
 */
function technologyPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'PostgreSQL',
        'category' => TechnologyCategory::Datastore->value,
        'ring' => TechnologyRing::Adopt->value,
        'status' => TechnologyStatus::Current->value,
        'vendor' => null,
        'homepage_url' => 'https://www.postgresql.org',
        'notes' => 'The default datastore.',
    ], $overrides);
}

test('guests cannot reach the inventory', function () {
    auth()->logout();

    $this->get(route('technologies.index'))->assertRedirect(route('login'));
    $this->get(route('technologies.breakdown'))->assertRedirect(route('login'));
});

test('a technology is added once and named uniquely', function () {
    $this->post(route('technologies.store'), technologyPayload())
        ->assertRedirect(route('technologies.show', Technology::sole()));

    expect(Technology::sole()->category)->toBe(TechnologyCategory::Datastore);

    $this->post(route('technologies.store'), technologyPayload())
        ->assertSessionHasErrors('name');

    expect(Technology::count())->toBe(1);
});

test('a homepage must be http or https', function () {
    $this->post(route('technologies.store'), technologyPayload(['homepage_url' => 'file:///etc']))
        ->assertSessionHasErrors('homepage_url');
});

test('the inventory can be narrowed by what it is and what we think of it', function () {
    Technology::factory()->create(['name' => 'Go', 'category' => TechnologyCategory::Language]);
    Technology::factory()->held()->create(['name' => 'Vue', 'category' => TechnologyCategory::Framework]);
    Technology::factory()->deprecated()->create(['name' => 'Redis', 'category' => TechnologyCategory::Datastore]);

    $this->get(route('technologies.index', ['category' => 'language']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('technologies', 1));

    $this->get(route('technologies.index', ['ring' => 'hold']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('technologies', 1)
            ->where('technologies.0.name', 'Vue'));

    $this->get(route('technologies.index', ['status' => 'deprecated']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('technologies', 1)
            ->where('technologies.0.name', 'Redis'));
});

test('every kind of record can carry a technology', function () {
    $technology = Technology::factory()->create();

    $carriers = [
        'project' => Project::factory()->create(),
        'prototype' => Prototype::factory()->create(),
        'decision_record' => DecisionRecord::factory()->create(),
        'security_note' => SecurityNote::factory()->create(),
    ];

    foreach ($carriers as $type => $record) {
        $this->post(route('technology-usages.store'), [
            'technology_id' => $technology->id,
            'usable_type' => $type,
            'usable_id' => $record->id,
        ])->assertSessionHasNoErrors();
    }

    expect(TechnologyUsage::count())->toBe(4);
});

test('it refuses a record that cannot carry one, or does not exist', function () {
    $technology = Technology::factory()->create();

    $this->post(route('technology-usages.store'), [
        'technology_id' => $technology->id,
        'usable_type' => 'radar_item',
        'usable_id' => 1,
    ])->assertSessionHasErrors('usable_type');

    $this->post(route('technology-usages.store'), [
        'technology_id' => $technology->id,
        'usable_type' => 'project',
        'usable_id' => 9999,
    ])->assertSessionHasErrors('usable_id');

    expect(TechnologyUsage::count())->toBe(0);
});

test('the same technology is not recorded twice on one record', function () {
    $technology = Technology::factory()->create();
    $project = Project::factory()->create();

    $payload = [
        'technology_id' => $technology->id,
        'usable_type' => 'project',
        'usable_id' => $project->id,
        'version' => '18',
    ];

    $this->post(route('technology-usages.store'), $payload)->assertSessionHasNoErrors();
    $this->post(route('technology-usages.store'), $payload)->assertSessionHasErrors('technology_id');

    expect(TechnologyUsage::count())->toBe(1);
});

test('the version lives on the use, so one technology can run at several', function () {
    $postgres = Technology::factory()->create(['name' => 'PostgreSQL']);
    $one = Project::factory()->create();
    $two = Project::factory()->create();

    TechnologyUsage::factory()->on($one)->create([
        'technology_id' => $postgres->id, 'version' => '18',
    ]);
    TechnologyUsage::factory()->on($two)->create([
        'technology_id' => $postgres->id, 'version' => '15',
    ]);

    $this->get(route('technologies.show', $postgres))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('versions', ['15', '18'])
            ->has('usages', 1)
            ->where('usages.0.label', 'Projects')
            ->has('usages.0.records', 2));
});

test('the show page groups the places it is used by kind', function () {
    $technology = Technology::factory()->create();
    $project = Project::factory()->create(['name' => 'Vision', 'prefix' => 'VNG']);
    $prototype = Prototype::factory()->create(['title' => 'A spike']);

    TechnologyUsage::factory()->on($project)->create(['technology_id' => $technology->id]);
    TechnologyUsage::factory()->on($prototype)->create(['technology_id' => $technology->id]);

    $this->get(route('technologies.show', $technology))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use ($project, $prototype) {
            $groups = collect($page->toArray()['props']['usages'])->keyBy('label');

            expect($groups['Projects']['records'][0]['label'])->toBe('VNG — Vision')
                ->and($groups['Projects']['records'][0]['url'])->toBe(route('projects.show', $project))
                ->and($groups['Prototypes']['records'][0]['label'])->toBe('A spike')
                ->and($groups['Prototypes']['records'][0]['url'])->toBe(route('prototypes.show', $prototype));
        });
});

test('removing a technology takes its entries with it', function () {
    $technology = Technology::factory()->create();
    TechnologyUsage::factory()->create(['technology_id' => $technology->id]);

    $this->delete(route('technologies.destroy', $technology))
        ->assertRedirect(route('technologies.index'));

    expect(TechnologyUsage::count())->toBe(0);
});

test('an entry can be removed without touching the technology', function () {
    $usage = TechnologyUsage::factory()->create();

    $this->delete(route('technology-usages.destroy', $usage))->assertRedirect();

    expect(TechnologyUsage::count())->toBe(0)
        ->and(Technology::count())->toBe(1);
});

test('a read-only account can read the inventory but not change it', function () {
    auth()->logout();
    $this->actingAs(User::factory()->readOnly()->create());

    $technology = Technology::factory()->create();

    $this->get(route('technologies.index'))->assertOk();
    $this->get(route('technologies.breakdown'))->assertOk();
    $this->get(route('technologies.show', $technology))->assertOk();

    $this->post(route('technologies.store'), technologyPayload())->assertForbidden();
    $this->post(route('technology-usages.store'), [
        'technology_id' => $technology->id, 'usable_type' => 'project', 'usable_id' => 1,
    ])->assertForbidden();
});
