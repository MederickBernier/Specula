<?php

use App\Enums\DecisionStatus;
use App\Models\DecisionLink;
use App\Models\DecisionOption;
use App\Models\DecisionRecord;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * @return array<string, mixed>
 */
function decisionPayload(array $overrides = []): array
{
    return array_merge([
        'project_prefix' => 'VNG',
        'category' => 'ARCH',
        'sequence' => 1,
        'title' => 'Pick a frontend stack',
        'status' => DecisionStatus::Draft->value,
        'author' => 'Mederick',
        'deciders' => 'N/A',
        'affects' => 'web',
        'proposal_context' => 'Some **context**.',
        'recommendation' => 'Go with Inertia.',
        'consequences' => null,
        'conditions_for_revisiting' => null,
        'options' => [
            ['name' => 'Livewire', 'description' => 'Server driven', 'pros' => 'Fast', 'cons' => 'No React', 'was_chosen' => false],
            ['name' => 'Inertia', 'description' => 'React based', 'pros' => 'Practice', 'cons' => 'Toolchain', 'was_chosen' => true],
        ],
    ], $overrides);
}

test('guests cannot reach the decision records', function () {
    auth()->logout();

    $this->get(route('decisions.index'))->assertRedirect(route('login'));
    $this->get(route('decisions.create'))->assertRedirect(route('login'));
    $this->post(route('decisions.store'), [])->assertRedirect(route('login'));
});

test('the index lists records ordered by prefix, category and sequence', function () {
    DecisionRecord::factory()->create(['project_prefix' => 'VNG', 'category' => 'ARCH', 'sequence' => 2]);
    DecisionRecord::factory()->create(['project_prefix' => 'VNG', 'category' => 'ARCH', 'sequence' => 1]);
    DecisionRecord::factory()->create(['project_prefix' => 'ACME', 'category' => 'INFRA', 'sequence' => 9]);

    $this->get(route('decisions.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('decisions/index')
            ->has('records', 3)
            ->where('records.0.document_id', 'ACME-INFRA-009')
            ->where('records.1.document_id', 'VNG-ARCH-001')
            ->where('records.2.document_id', 'VNG-ARCH-002'));
});

test('the create page exposes the status options', function () {
    $this->get(route('decisions.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('decisions/create')
            ->has('statuses', 4)
            ->where('statuses.0.value', 'draft'));
});

test('it stores a record with its options', function () {
    $this->post(route('decisions.store'), decisionPayload())
        ->assertRedirect(route('decisions.show', DecisionRecord::first()));

    $record = DecisionRecord::first();

    expect($record->document_id)->toBe('VNG-ARCH-001')
        ->and($record->status)->toBe(DecisionStatus::Draft)
        ->and($record->options)->toHaveCount(2)
        ->and($record->options->firstWhere('name', 'Inertia')->was_chosen)->toBeTrue();
});

test('it rejects a duplicate prefix, category and sequence', function () {
    DecisionRecord::factory()->create(['project_prefix' => 'VNG', 'category' => 'ARCH', 'sequence' => 1]);

    $this->post(route('decisions.store'), decisionPayload())
        ->assertSessionHasErrors('sequence');

    expect(DecisionRecord::count())->toBe(1);
});

test('it updates a record and replaces its options', function () {
    $record = DecisionRecord::factory()->create(['project_prefix' => 'VNG', 'category' => 'ARCH', 'sequence' => 1]);
    DecisionOption::factory()->count(3)->create(['decision_record_id' => $record->id]);

    $this->put(route('decisions.update', $record), decisionPayload([
        'title' => 'Renamed',
        'status' => DecisionStatus::Decided->value,
        'options' => [['name' => 'Only one', 'was_chosen' => true]],
    ]))->assertRedirect(route('decisions.show', $record));

    $record->refresh();

    expect($record->title)->toBe('Renamed')
        ->and($record->status)->toBe(DecisionStatus::Decided)
        ->and($record->options)->toHaveCount(1)
        ->and($record->options->first()->name)->toBe('Only one');
});

test('the sequence uniqueness rule ignores the record being updated', function () {
    $record = DecisionRecord::factory()->create(['project_prefix' => 'VNG', 'category' => 'ARCH', 'sequence' => 1]);

    $this->put(route('decisions.update', $record), decisionPayload(['title' => 'Same sequence']))
        ->assertSessionHasNoErrors();

    expect($record->refresh()->title)->toBe('Same sequence');
});

test('it deletes a record and cascades its options', function () {
    $record = DecisionRecord::factory()->create();
    DecisionOption::factory()->count(2)->create(['decision_record_id' => $record->id]);

    $this->delete(route('decisions.destroy', $record))
        ->assertRedirect(route('decisions.index'));

    expect(DecisionRecord::count())->toBe(0)
        ->and(DecisionOption::count())->toBe(0);
});

test('the show page renders markdown and strips raw html', function () {
    $record = DecisionRecord::factory()->create([
        'proposal_context' => "Some **bold** text.\n\n<script>alert('x')</script>",
        'consequences' => null,
    ]);

    $this->get(route('decisions.show', $record))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('decisions/show')
            ->where('html.proposal_context', fn (string $html) => str_contains($html, '<strong>bold</strong>')
                && ! str_contains($html, '<script>'))
            ->where('html.consequences', null));
});

test('the show page offers every other record as a link target', function () {
    $record = DecisionRecord::factory()->create();
    DecisionRecord::factory()->count(2)->create();

    $this->get(route('decisions.show', $record))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('linkTargets', 2)
            ->has('relationshipTypes', 3));
});

test('the show page exposes both link directions to the frontend', function () {
    $record = DecisionRecord::factory()->create();
    $other = DecisionRecord::factory()->create();

    DecisionLink::factory()->create(['source_id' => $record->id, 'target_id' => $other->id]);
    DecisionLink::factory()->create(['source_id' => $other->id, 'target_id' => $record->id]);

    $this->get(route('decisions.show', $record))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('record.outgoing_links', 1)
            ->has('record.incoming_links', 1)
            ->where('record.outgoing_links.0.target.document_id', $other->document_id));
});
