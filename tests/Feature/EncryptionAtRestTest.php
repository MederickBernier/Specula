<?php

use App\Models\DecisionOption;
use App\Models\DecisionRecord;
use App\Models\Project;
use App\Models\Prototype;
use App\Models\RadarItem;
use App\Models\SecurityNote;
use App\Models\User;
use App\Models\VettingItem;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

/**
 * @return object|null
 */
function rawRow(string $table, int $id)
{
    return DB::table($table)->where('id', $id)->first();
}

test('the substance of the work is not readable in the database', function () {
    $project = Project::factory()->create(['name' => 'Vision Next Gen']);
    $decision = DecisionRecord::factory()->create([
        'title' => 'Pick a frontend stack',
        'proposal_context' => 'The client is a desktop application today.',
    ]);
    $vetting = VettingItem::factory()->create(['title' => 'Offline mode']);
    $prototype = Prototype::factory()->create(['hypothesis' => 'A service worker can hold it.']);
    $finding = SecurityNote::factory()->create([
        'title' => 'Presigned URLs never expire',
        'finding' => 'The bucket policy grants write to any signed caller.',
    ]);

    expect(rawRow('projects', $project->id)->name)->not->toContain('Vision')
        ->and(rawRow('decision_records', $decision->id)->title)->not->toContain('frontend')
        ->and(rawRow('decision_records', $decision->id)->proposal_context)->not->toContain('desktop')
        ->and(rawRow('vetting_items', $vetting->id)->title)->not->toContain('Offline')
        ->and(rawRow('prototypes', $prototype->id)->hypothesis)->not->toContain('service worker')
        ->and(rawRow('security_notes', $finding->id)->finding)->not->toContain('bucket policy');
});

test('what the app has to sort and filter on stays readable', function () {
    $decision = DecisionRecord::factory()->decided()->create([
        'project_prefix' => 'VNG',
        'category' => 'ARCH',
        'sequence' => 7,
    ]);
    $finding = SecurityNote::factory()->create();

    $raw = rawRow('decision_records', $decision->id);

    expect($raw->project_prefix)->toBe('VNG')
        ->and($raw->category)->toBe('ARCH')
        ->and($raw->sequence)->toBe(7)
        ->and($raw->status)->toBe('decided')
        ->and(rawRow('security_notes', $finding->id)->severity)->toBe($finding->severity->value);
});

test('the radar is left in the clear, because its url is the dedup key', function () {
    $item = RadarItem::factory()->create([
        'title' => 'Postgres 18 lands',
        'url' => 'https://example.test/pg18',
    ]);

    $raw = rawRow('radar_items', $item->id);

    expect($raw->title)->toBe('Postgres 18 lands')
        ->and($raw->url)->toBe('https://example.test/pg18');
});

test('reading it back through the app gives the text again', function () {
    $decision = DecisionRecord::factory()->create([
        'project_prefix' => 'VNG',
        'category' => 'ARCH',
        'sequence' => 1,
        'title' => 'Pick a frontend stack',
    ]);

    expect($decision->fresh()->title)->toBe('Pick a frontend stack')
        ->and($decision->fresh()->document_id)->toBe('VNG-ARCH-001');
});

test('search still finds encrypted text, including inside a decision option', function () {
    $this->actingAs(User::factory()->create());

    $record = DecisionRecord::factory()->create(['title' => 'How to split the services']);
    DecisionOption::factory()->create([
        'decision_record_id' => $record->id,
        'name' => 'Microservices',
        'cons' => 'Too much for a team this size.',
    ]);

    VettingItem::factory()->create(['title' => 'Unrelated']);

    $this->get(route('search', ['q' => 'microservices']))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $groups = collect($page->toArray()['props']['groups'])->keyBy('module');

            expect($groups['Decision records']['total'])->toBe(1)
                ->and($groups['Decision records']['results'][0]['label'])
                ->toContain('How to split the services');
        });
});

test('search stays case-insensitive on encrypted text', function () {
    $this->actingAs(User::factory()->create());

    SecurityNote::factory()->create(['finding' => 'A KUBERNETES misconfiguration.']);

    $this->get(route('search', ['q' => 'kubernetes']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('total', 1));
});

test('projects are listed in name order despite the name being encrypted', function () {
    $this->actingAs(User::factory()->create());

    Project::factory()->create(['name' => 'Zebra', 'prefix' => 'ZED']);
    Project::factory()->create(['name' => 'Alpha', 'prefix' => 'ALP']);
    Project::factory()->create(['name' => 'Middle', 'prefix' => 'MID']);

    $this->get(route('projects.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('projects.0.name', 'Alpha')
            ->where('projects.1.name', 'Middle')
            ->where('projects.2.name', 'Zebra'));
});

test('a radar item that is refetched is still recognised as the same item', function () {
    // The dedup key would stop working if the radar were encrypted too.
    RadarItem::factory()->create(['url' => 'https://example.test/one']);

    expect(RadarItem::query()->whereIn('url', ['https://example.test/one'])->count())->toBe(1);
});
