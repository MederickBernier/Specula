<?php

use App\Actions\RenderDecisionRecordMarkdown;
use App\Enums\DecisionRelationshipType;
use App\Models\DecisionLink;
use App\Models\DecisionOption;
use App\Models\DecisionRecord;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\Prototype;
use App\Models\SecurityNote;
use App\Models\User;
use App\Models\VettingItem;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('a decision downloads as a markdown document named after it', function () {
    $record = DecisionRecord::factory()->decided()->create([
        'project_prefix' => 'VNG',
        'category' => 'ARCH',
        'sequence' => 1,
        'title' => 'Pick a frontend stack',
        'author' => 'Mederick',
        'proposal_context' => 'The client is a desktop app today.',
        'recommendation' => 'Go with Inertia and React.',
        'consequences' => 'A JS toolchain to keep current.',
    ]);

    $response = $this->get(route('decisions.export', $record));

    $response->assertOk()
        ->assertHeader('content-type', 'text/markdown; charset=UTF-8')
        ->assertHeader('content-disposition', 'attachment; filename="VNG-ARCH-001.md"');

    $markdown = $response->getContent();

    expect($markdown)
        ->toContain('# VNG-ARCH-001 — Pick a frontend stack')
        ->toContain('| Status | Decided |')
        ->toContain('| Author | Mederick |')
        ->toContain('## Context')
        ->toContain('The client is a desktop app today.')
        ->toContain('## Decision')
        ->toContain('Go with Inertia and React.')
        ->toContain('## Consequences');
});

test('it leaves out the sections that were never written', function () {
    $record = DecisionRecord::factory()->create([
        'consequences' => null,
        'conditions_for_revisiting' => null,
    ]);

    $markdown = app(RenderDecisionRecordMarkdown::class)($record);

    expect($markdown)->not->toContain('## Consequences')
        ->and($markdown)->not->toContain('## Conditions for revisiting');
});

test('it writes the options with the chosen one marked', function () {
    $record = DecisionRecord::factory()->create();

    DecisionOption::factory()->create([
        'decision_record_id' => $record->id,
        'name' => 'Livewire',
        'pros' => 'Fast to build',
        'was_chosen' => false,
    ]);
    DecisionOption::factory()->chosen()->create([
        'decision_record_id' => $record->id,
        'name' => 'Inertia',
        'cons' => 'A JS toolchain',
    ]);

    $markdown = app(RenderDecisionRecordMarkdown::class)($record->fresh());

    expect($markdown)
        ->toContain('## Options considered')
        ->toContain('### Livewire')
        ->toContain('**Pros:** Fast to build')
        ->toContain('### Inertia *(chosen)*')
        ->toContain('**Cons:** A JS toolchain');
});

test('it writes the cross-references as a table, both directions', function () {
    $source = DecisionRecord::factory()->create(['project_prefix' => 'VNG', 'category' => 'ARCH', 'sequence' => 1]);
    $target = DecisionRecord::factory()->create(['project_prefix' => 'VNG', 'category' => 'ARCH', 'sequence' => 2]);

    DecisionLink::factory()->create([
        'source_id' => $source->id,
        'target_id' => $target->id,
        'relationship_type' => DecisionRelationshipType::Supersedes,
        'scope_note' => 'deployment model only',
    ]);

    expect(app(RenderDecisionRecordMarkdown::class)($source->fresh()))
        ->toContain('## Related decisions')
        ->toContain('| → | Supersedes | VNG-ARCH-002')
        ->toContain('deployment model only');

    expect(app(RenderDecisionRecordMarkdown::class)($target->fresh()))
        ->toContain('| ← | Supersedes | VNG-ARCH-001');
});

test('a pipe in free text does not break the table it sits in', function () {
    $record = DecisionRecord::factory()->create(['deciders' => 'Alex | Sam']);

    expect(app(RenderDecisionRecordMarkdown::class)($record))
        ->toContain('| Deciders | Alex \\| Sam |');
});

test('the show page carries the markdown so it can be copied without a round trip', function () {
    $record = DecisionRecord::factory()->create(['title' => 'Pick a stack']);

    $this->get(route('decisions.show', $record))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('markdown', fn (string $md) => str_contains($md, 'Pick a stack')));
});

test('a project downloads as one document holding its decisions and notes', function () {
    $project = Project::factory()->create(['prefix' => 'VNG', 'name' => 'Vision Next Gen']);

    DecisionRecord::factory()->create([
        'project_id' => $project->id,
        'category' => 'ARCH',
        'sequence' => 1,
        'title' => 'Pick a frontend stack',
    ]);
    ProjectNote::factory()->create([
        'project_id' => $project->id,
        'title' => 'Kickoff',
        'body' => 'Met the team.',
    ]);
    VettingItem::factory()->create(['project_id' => $project->id, 'title' => 'Queue the reports']);
    VettingItem::factory()->vetted()->create(['project_id' => $project->id, 'title' => 'Already settled']);
    Prototype::factory()->create(['project_id' => $project->id, 'title' => 'Spike the queue']);
    SecurityNote::factory()->create(['project_id' => $project->id, 'title' => 'Open finding']);

    $response = $this->get(route('projects.export', $project));

    $response->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename="vng.md"');

    $markdown = $response->getContent();

    expect($markdown)
        ->toContain('# VNG — Vision Next Gen')
        ->toContain('## Decision records')
        ->toContain('### VNG-ARCH-001 — Pick a frontend stack')
        ->toContain('## Still open')
        ->toContain('| Vetting | Queue the reports |')
        ->toContain('| Prototype | Spike the queue |')
        ->toContain('| Security | Open finding |')
        ->toContain('## Notes')
        ->toContain('### Kickoff')
        // resolved work is not "still open"
        ->not->toContain('Already settled');
});

test('an archived project says so in its document', function () {
    $project = Project::factory()->archived()->create();

    $response = $this->get(route('projects.export', $project));

    expect($response->getContent())->toContain('*Archived ');
});

test('a read-only account can export', function () {
    auth()->logout();
    $this->actingAs(User::factory()->readOnly()->create());

    $record = DecisionRecord::factory()->create();
    $project = Project::factory()->create();

    $this->get(route('decisions.export', $record))->assertOk();
    $this->get(route('projects.export', $project))->assertOk();
});

test('guests cannot export', function () {
    auth()->logout();

    $record = DecisionRecord::factory()->create();

    $this->get(route('decisions.export', $record))->assertRedirect(route('login'));
});
