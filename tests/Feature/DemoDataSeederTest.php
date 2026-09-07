<?php

use App\Enums\DecisionStatus;
use App\Models\DecisionLink;
use App\Models\DecisionRecord;
use App\Models\ItemLink;
use App\Models\Project;
use App\Models\Prototype;
use App\Models\SecurityNote;
use App\Models\VettingItem;
use Database\Seeders\DemoDataSeeder;

test('it fills every module with something to look at', function () {
    $this->seed(DemoDataSeeder::class);

    expect(Project::count())->toBe(3)
        ->and(DecisionRecord::count())->toBeGreaterThan(0)
        ->and(VettingItem::count())->toBeGreaterThan(0)
        ->and(Prototype::count())->toBeGreaterThan(0)
        ->and(SecurityNote::count())->toBeGreaterThan(0)
        ->and(DecisionLink::count())->toBeGreaterThan(0)
        ->and(ItemLink::count())->toBeGreaterThan(0);
});

test('the demo data exercises the states worth seeing', function () {
    $this->seed(DemoDataSeeder::class);

    expect(DecisionRecord::where('status', DecisionStatus::Superseded)->exists())->toBeTrue()
        ->and(DecisionRecord::whereNotNull('next_review_at')->exists())->toBeTrue()
        ->and(SecurityNote::query()->deferralElapsed()->exists())->toBeTrue()
        ->and(SecurityNote::where('is_issue', false)->exists())->toBeTrue()
        ->and(Prototype::whereNotNull('date_completed')->exists())->toBeTrue()
        ->and(Project::query()->archived()->exists())->toBeTrue()
        ->and(VettingItem::whereNull('date_resolved')->exists())->toBeTrue();
});

test('a decision filed under a project carries that project prefix', function () {
    $this->seed(DemoDataSeeder::class);

    $vng = Project::where('prefix', 'VNG')->sole();

    expect($vng->decisionRecords()->pluck('project_prefix')->unique()->all())->toBe(['VNG']);
});

test('running it twice does not double the data', function () {
    $this->seed(DemoDataSeeder::class);
    $projects = Project::count();
    $decisions = DecisionRecord::count();

    $this->seed(DemoDataSeeder::class);

    expect(Project::count())->toBe($projects)
        ->and(DecisionRecord::count())->toBe($decisions);
});

test('it refuses to run in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    (new DemoDataSeeder)->run();

    expect(Project::count())->toBe(0);
});
