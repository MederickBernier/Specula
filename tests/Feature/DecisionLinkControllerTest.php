<?php

use App\Enums\DecisionRelationshipType;
use App\Models\DecisionLink;
use App\Models\DecisionRecord;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->source = DecisionRecord::factory()->create();
    $this->target = DecisionRecord::factory()->create();
});

test('it links two records', function () {
    $this->post(route('decisions.links.store', $this->source), [
        'target_id' => $this->target->id,
        'relationship_type' => DecisionRelationshipType::Supersedes->value,
        'role_note' => 'Upstream',
    ])->assertRedirect(route('decisions.show', $this->source));

    expect($this->source->outgoingLinks)->toHaveCount(1)
        ->and($this->target->incomingLinks->first()->role_note)->toBe('Upstream');
});

test('it rejects a record linked to itself', function () {
    $this->post(route('decisions.links.store', $this->source), [
        'target_id' => $this->source->id,
        'relationship_type' => DecisionRelationshipType::RelatedTo->value,
    ])->assertSessionHasErrors('target_id');

    expect(DecisionLink::count())->toBe(0);
});

test('it rejects a duplicate link of the same type', function () {
    DecisionLink::factory()->create([
        'source_id' => $this->source->id,
        'target_id' => $this->target->id,
        'relationship_type' => DecisionRelationshipType::Constrains,
    ]);

    $this->post(route('decisions.links.store', $this->source), [
        'target_id' => $this->target->id,
        'relationship_type' => DecisionRelationshipType::Constrains->value,
    ])->assertSessionHasErrors('relationship_type');

    expect(DecisionLink::count())->toBe(1);
});

test('it removes a link and returns to its source record', function () {
    $link = DecisionLink::factory()->create([
        'source_id' => $this->source->id,
        'target_id' => $this->target->id,
    ]);

    $this->delete(route('decisions.links.destroy', $link))
        ->assertRedirect(route('decisions.show', $this->source));

    expect(DecisionLink::count())->toBe(0);
});
