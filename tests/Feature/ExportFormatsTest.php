<?php

use App\Enums\SecurityNoteStatus;
use App\Enums\SecuritySeverity;
use App\Enums\TechnologyCategory;
use App\Models\DecisionOption;
use App\Models\DecisionRecord;
use App\Models\Project;
use App\Models\Prototype;
use App\Models\SecurityNote;
use App\Models\Technology;
use App\Models\TechnologyUsage;
use App\Models\User;
use App\Models\VettingItem;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('a proposal exports with its assessment and its verdict', function () {
    $item = VettingItem::factory()->rejected()->create([
        'title' => 'Rewrite the model layer',
        'proposal_description' => 'Take the chance while the client is replaced.',
        'assessment' => 'Two rewrites at once is one too many.',
        'rejection_reason' => 'The client replacement is already the risky change.',
        'date_raised' => '2026-03-01',
    ]);

    $response = $this->get(route('vetting.export', $item));

    $response->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename="vetting-'.$item->id.'.md"');

    expect($response->getContent())
        ->toContain('# Rewrite the model layer')
        ->toContain('| Status | Rejected |')
        ->toContain('## Proposal')
        ->toContain('## Assessment')
        ->toContain('## Why it was rejected');
});

test('a finished spike exports its result, an abandoned one its reason', function () {
    $completed = Prototype::factory()->completed()->create([
        'title' => 'Offline job sheets',
        'result' => 'It works, sync is the hard part.',
    ]);
    $abandoned = Prototype::factory()->abandoned()->create([
        'title' => 'Server side rendering',
        'result' => 'Never written.',
        'abandoned_reason' => 'Deprioritised.',
    ]);

    expect($this->get(route('prototypes.export', $completed))->getContent())
        ->toContain('## Result')
        ->toContain('It works, sync is the hard part.')
        ->not->toContain('## Why it was abandoned');

    expect($this->get(route('prototypes.export', $abandoned))->getContent())
        ->toContain('## Why it was abandoned')
        // the stray result is not printed for a spike that never produced one
        ->not->toContain('## Result');
});

test('a finding exports with its triage call', function () {
    $note = SecurityNote::factory()->nonIssue()->create([
        'title' => 'Debug endpoint reachable',
        'finding' => 'A reporter found /debug responding.',
        'non_issue_reason' => 'Only reachable through a VPN they already had.',
        'severity' => SecuritySeverity::High,
        'status' => SecurityNoteStatus::NonIssue,
    ]);

    expect($this->get(route('security-notes.export', $note))->getContent())
        ->toContain('# Debug endpoint reachable')
        ->toContain('| Severity | High |')
        ->toContain('| A real issue | No |')
        ->toContain('## Why it is not an issue');
});

test('a technology exports with everywhere it runs', function () {
    $postgres = Technology::factory()->create([
        'name' => 'PostgreSQL',
        'category' => TechnologyCategory::Datastore,
    ]);
    $project = Project::factory()->create(['name' => 'Vision', 'prefix' => 'VNG']);

    TechnologyUsage::factory()->on($project)->create([
        'technology_id' => $postgres->id,
        'version' => '18',
        'role' => 'primary datastore',
    ]);

    $response = $this->get(route('technologies.export', $postgres));

    $response->assertHeader('content-disposition', 'attachment; filename="postgresql.md"');

    expect($response->getContent())
        ->toContain('# PostgreSQL')
        ->toContain('## Where it is used')
        ->toContain('| Project | VNG — Vision | 18 | primary datastore |');
});

test('the archive gathers projects, unfiled work and the inventory', function () {
    $project = Project::factory()->create(['name' => 'Vision', 'prefix' => 'VNG']);

    DecisionRecord::factory()->create([
        'project_id' => $project->id,
        'category' => 'ARCH',
        'sequence' => 1,
        'title' => 'Pick a stack',
    ]);

    VettingItem::factory()->create(['title' => 'Unfiled proposal', 'project_id' => null]);
    Prototype::factory()->create(['title' => 'Unfiled spike', 'project_id' => null]);
    SecurityNote::factory()->create(['title' => 'Unfiled finding', 'project_id' => null]);
    Technology::factory()->create(['name' => 'Redis']);

    $response = $this->get(route('export.everything'));

    $response->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename="specula-'.now()->toDateString().'.md"');

    expect($response->getContent())
        ->toContain('# Specula')
        // projects sit at level two, so the deepest heading inside one still
        // lands within the six markdown has
        ->toContain('## VNG — Vision')
        // a project's decisions come along with it
        ->toContain('VNG-ARCH-001 — Pick a stack')
        ->toContain('## Filed under no project')
        ->toContain('Unfiled proposal')
        ->toContain('Unfiled spike')
        ->toContain('Unfiled finding')
        ->toContain('## Technology inventory')
        ->toContain('Redis');
});

test('an archive of an empty instance is still a document', function () {
    expect($this->get(route('export.everything'))->getContent())
        ->toContain('# Specula')
        ->not->toContain('## Filed under no project')
        ->not->toContain('## Technology inventory');
});

test('every export can be had as a pdf', function () {
    $records = [
        route('decisions.export', DecisionRecord::factory()->create()),
        route('projects.export', Project::factory()->create()),
        route('vetting.export', VettingItem::factory()->create()),
        route('prototypes.export', Prototype::factory()->create()),
        route('security-notes.export', SecurityNote::factory()->create()),
        route('technologies.export', Technology::factory()->create()),
        route('export.everything'),
    ];

    foreach ($records as $url) {
        $response = $this->get($url.'?format=pdf');

        $response->assertOk()->assertHeader('content-type', 'application/pdf');

        $body = $response->getContent();

        expect($body)->toStartWith('%PDF-')
            ->and(strlen($body))->toBeGreaterThan(1000);
    }
});

test('a pdf is named after the record, not the format asked for', function () {
    $record = DecisionRecord::factory()->create([
        'project_prefix' => 'VNG', 'category' => 'ARCH', 'sequence' => 3,
    ]);

    // dompdf writes the filename unquoted, which is valid and what it sends
    $this->get(route('decisions.export', $record).'?format=pdf')
        ->assertHeader('content-disposition', 'attachment; filename=VNG-ARCH-003.pdf');
});

test('an unrecognised format falls back to markdown rather than guessing', function () {
    $record = DecisionRecord::factory()->create();

    $this->get(route('decisions.export', $record).'?format=docx')
        ->assertOk()
        ->assertHeader('content-type', 'text/markdown; charset=UTF-8');
});

test('a read-only account can export anything it can read', function () {
    auth()->logout();
    $this->actingAs(User::factory()->readOnly()->create());

    $this->get(route('vetting.export', VettingItem::factory()->create()))->assertOk();
    $this->get(route('export.everything'))->assertOk();
    $this->get(route('export.everything').'?format=pdf')->assertOk();
});

test('guests cannot export', function () {
    auth()->logout();

    $this->get(route('export.everything'))->assertRedirect(route('login'));
    $this->get(route('vetting.export', VettingItem::factory()->create()))
        ->assertRedirect(route('login'));
});

test('no heading goes deeper than markdown allows', function () {
    $project = Project::factory()->create();
    $record = DecisionRecord::factory()->create(['project_id' => $project->id]);

    DecisionOption::factory()->create([
        'decision_record_id' => $record->id,
        'name' => 'An option',
    ]);

    // A decision nests inside a project inside the archive, and an option nests
    // inside the decision. Without a clamp the deepest heading renders as
    // literal hashes rather than a heading.
    preg_match_all('/^(#+) /m', $this->get(route('export.everything'))->getContent(), $matches);

    expect($matches[1])->not->toBeEmpty()
        ->and(max(array_map('strlen', $matches[1])))->toBeLessThanOrEqual(6);
});
