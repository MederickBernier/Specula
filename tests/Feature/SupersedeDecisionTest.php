<?php

use App\Enums\DecisionRelationshipType;
use App\Enums\DecisionStatus;
use App\Models\DecisionLink;
use App\Models\DecisionOption;
use App\Models\DecisionRecord;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests cannot supersede', function () {
    auth()->logout();

    $record = DecisionRecord::factory()->create();

    $this->post(route('decisions.supersede', $record), ['title' => 'x'])
        ->assertRedirect(route('login'));
});

test('superseding writes the replacement, links it, and retires the original', function () {
    $project = Project::factory()->create(['prefix' => 'VNG']);
    $record = DecisionRecord::factory()->decided()->create([
        'project_id' => $project->id,
        'category' => 'ARCH',
        'sequence' => 1,
        'title' => 'Pick a frontend stack',
        'author' => 'Mederick',
        'deciders' => 'N/A',
        'affects' => 'web',
        'proposal_context' => 'The client is a desktop app today.',
    ]);

    $this->post(route('decisions.supersede', $record), [
        'title' => 'Pick a frontend stack, again',
    ])->assertRedirect(route('decisions.edit', DecisionRecord::latest('id')->first()));

    $successor = DecisionRecord::latest('id')->first();

    expect($successor->document_id)->toBe('VNG-ARCH-002')
        ->and($successor->status)->toBe(DecisionStatus::Draft)
        ->and($successor->project_id)->toBe($project->id)
        ->and($successor->author)->toBe('Mederick')
        ->and($successor->deciders)->toBe('N/A')
        ->and($successor->affects)->toBe('web')
        // the question carries over, the answer does not
        ->and($successor->proposal_context)->toBe('The client is a desktop app today.')
        ->and($successor->recommendation)->toBe('');

    $link = DecisionLink::sole();

    expect($link->source_id)->toBe($successor->id)
        ->and($link->target_id)->toBe($record->id)
        ->and($link->relationship_type)->toBe(DecisionRelationshipType::Supersedes)
        ->and($link->scope_note)->toBeNull();

    expect($record->refresh()->status)->toBe(DecisionStatus::Superseded);
});

test('a partial supersession leaves the earlier record standing', function () {
    $record = DecisionRecord::factory()->decided()->create([
        'project_prefix' => 'VNG', 'category' => 'ARCH', 'sequence' => 1,
    ]);

    $this->post(route('decisions.supersede', $record), [
        'title' => 'Revisit the deployment model',
        'scope_note' => 'deployment model section only',
        'impact_summary' => 'Everything else in the original still holds.',
    ])->assertRedirect();

    // Deliberately unedited: the original is the snapshot of what was true.
    expect($record->refresh()->status)->toBe(DecisionStatus::Decided);

    $link = DecisionLink::sole();

    expect($link->scope_note)->toBe('deployment model section only')
        ->and($link->impact_summary)->toBe('Everything else in the original still holds.');
});

test('a blank scope note is treated as a full supersession', function () {
    $record = DecisionRecord::factory()->decided()->create();

    $this->post(route('decisions.supersede', $record), [
        'title' => 'Replacement',
        'scope_note' => '   ',
    ]);

    expect(DecisionLink::sole()->scope_note)->toBeNull()
        ->and($record->refresh()->status)->toBe(DecisionStatus::Superseded);
});

test('the replacement takes the next free number in its category', function () {
    $record = DecisionRecord::factory()->create([
        'project_prefix' => 'VNG', 'category' => 'ARCH', 'sequence' => 1,
    ]);
    DecisionRecord::factory()->create([
        'project_prefix' => 'VNG', 'category' => 'ARCH', 'sequence' => 7,
    ]);
    // A different category must not push the number along.
    DecisionRecord::factory()->create([
        'project_prefix' => 'VNG', 'category' => 'INFRA', 'sequence' => 99,
    ]);

    $this->post(route('decisions.supersede', $record), ['title' => 'Replacement']);

    expect(DecisionRecord::latest('id')->first()->document_id)->toBe('VNG-ARCH-008');
});

test('the options weighed in the original are not carried over', function () {
    $record = DecisionRecord::factory()->create();
    DecisionOption::factory()->count(2)->create(['decision_record_id' => $record->id]);

    $this->post(route('decisions.supersede', $record), ['title' => 'Replacement']);

    expect(DecisionRecord::latest('id')->first()->options)->toHaveCount(0)
        ->and(DecisionOption::count())->toBe(2);
});

test('the replacement needs a title', function () {
    $record = DecisionRecord::factory()->create();

    $this->post(route('decisions.supersede', $record), ['title' => ''])
        ->assertSessionHasErrors('title');

    expect(DecisionRecord::count())->toBe(1)
        ->and(DecisionLink::count())->toBe(0);
});

test('both records show the relationship from their own side', function () {
    $record = DecisionRecord::factory()->decided()->create([
        'project_prefix' => 'VNG', 'category' => 'ARCH', 'sequence' => 1,
    ]);

    $this->post(route('decisions.supersede', $record), ['title' => 'Replacement']);

    $successor = DecisionRecord::latest('id')->first();

    expect($successor->outgoingLinks)->toHaveCount(1)
        ->and($record->refresh()->incomingLinks)->toHaveCount(1)
        ->and($record->incomingLinks->first()->source->is($successor))->toBeTrue();
});

test('a read-only account cannot supersede', function () {
    auth()->logout();
    $this->actingAs(User::factory()->readOnly()->create());

    $record = DecisionRecord::factory()->decided()->create();

    $this->post(route('decisions.supersede', $record), ['title' => 'Replacement'])
        ->assertForbidden();

    expect(DecisionRecord::count())->toBe(1)
        ->and($record->refresh()->status)->toBe(DecisionStatus::Decided);
});
