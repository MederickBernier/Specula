<?php

use App\Enums\TechnologyCategory;
use App\Models\Project;
use App\Models\Prototype;
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
function breakdown(AssertableInertia $page): array
{
    return $page->toArray()['props'];
}

test('it lays technologies against projects, with the version in the cell', function () {
    $vng = Project::factory()->create(['name' => 'Vision', 'prefix' => 'VNG']);
    $cra = Project::factory()->create(['name' => 'Compliance', 'prefix' => 'CRA']);

    $postgres = Technology::factory()->create([
        'name' => 'PostgreSQL', 'category' => TechnologyCategory::Datastore,
    ]);

    TechnologyUsage::factory()->on($vng)->create(['technology_id' => $postgres->id, 'version' => '18']);
    TechnologyUsage::factory()->on($cra)->create(['technology_id' => $postgres->id, 'version' => '15']);

    $this->get(route('technologies.breakdown'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use ($vng, $cra) {
            $props = breakdown($page);
            $row = $props['categories'][0]['technologies'][0];

            expect($props['projects'])->toHaveCount(2)
                ->and($props['categories'][0]['label'])->toBe('Datastore')
                ->and($row['name'])->toBe('PostgreSQL')
                ->and($row['projects'][$vng->id])->toBe('18')
                ->and($row['projects'][$cra->id])->toBe('15')
                ->and($row['total'])->toBe(2);
        });
});

test('a technology used with no version still marks the project', function () {
    $project = Project::factory()->create();
    $technology = Technology::factory()->create();

    TechnologyUsage::factory()->on($project)->create([
        'technology_id' => $technology->id, 'version' => null,
    ]);

    $this->get(route('technologies.breakdown'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use ($project) {
            $row = breakdown($page)['categories'][0]['technologies'][0];

            expect($row['projects'])->toHaveKey($project->id)
                ->and($row['projects'][$project->id])->toBe('');
        });
});

test('use outside a project is counted separately rather than lost', function () {
    $technology = Technology::factory()->create();
    $prototype = Prototype::factory()->create();

    TechnologyUsage::factory()->on($prototype)->create(['technology_id' => $technology->id]);

    $this->get(route('technologies.breakdown'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $row = breakdown($page)['categories'][0]['technologies'][0];

            expect($row['projects'])->toBe([])
                ->and($row['elsewhere'])->toBe(['prototype' => 1])
                ->and($row['total'])->toBe(1);
        });
});

test('technologies are grouped by category, and empty categories are left out', function () {
    Technology::factory()->create(['name' => 'Go', 'category' => TechnologyCategory::Language]);
    Technology::factory()->create(['name' => 'Rust', 'category' => TechnologyCategory::Language]);
    Technology::factory()->create(['name' => 'Redis', 'category' => TechnologyCategory::Datastore]);

    $this->get(route('technologies.breakdown'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $categories = collect(breakdown($page)['categories']);

            expect($categories)->toHaveCount(2)
                ->and($categories->pluck('label')->all())->toBe(['Language', 'Datastore'])
                ->and($categories->firstWhere('label', 'Language')['technologies'])->toHaveCount(2);
        });
});

test('it names what is held but still running, and what is not used at all', function () {
    $project = Project::factory()->create();

    $held = Technology::factory()->held()->create(['name' => 'Vue']);
    TechnologyUsage::factory()->on($project)->create(['technology_id' => $held->id]);

    // Held and not running anywhere: nothing to act on.
    Technology::factory()->held()->create(['name' => 'Backbone']);

    Technology::factory()->create(['name' => 'Unused thing']);

    $this->get(route('technologies.breakdown'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $summary = breakdown($page)['summary'];

            expect($summary['technologies'])->toBe(3)
                ->and($summary['usages'])->toBe(1)
                ->and(collect($summary['heldButRunning'])->pluck('name')->all())->toBe(['Vue'])
                ->and(collect($summary['unused'])->pluck('name')->all())
                ->toBe(['Backbone', 'Unused thing']);
        });
});

test('an archived project is still shown, and said to be archived', function () {
    Project::factory()->archived()->create(['name' => 'Edge', 'prefix' => 'EDGE']);

    $this->get(route('technologies.breakdown'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $projects = breakdown($page)['projects'];

            expect($projects[0]['prefix'])->toBe('EDGE')
                ->and($projects[0]['archived'])->toBeTrue();
        });
});

test('an empty inventory reports nothing rather than breaking', function () {
    $this->get(route('technologies.breakdown'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('categories', 0)
            ->where('summary.technologies', 0)
            ->where('summary.usages', 0));
});
