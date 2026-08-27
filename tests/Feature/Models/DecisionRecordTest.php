<?php

use App\Enums\DecisionRelationshipType;
use App\Models\DecisionLink;
use App\Models\DecisionOption;
use App\Models\DecisionRecord;

test('it formats the document id with a zero padded sequence', function(){
    $record = DecisionRecord::factory()->create([
        'project_prefix' => 'VNG',
        'category' => 'ARCH',
        'sequence' => 1,
    ]);

    expect($record->document_id)->toBe('VNG-ARCH-001');
});

test('it does not pad a sequence beyond three digits', function(){
    $record = DecisionRecord::factory()->create(['sequence' => 1234]);

    expect($record->document_id)->toEndWith('-1234');
});

test('it exposes the document id when serialised for intertia', function(){
    $record = DecisionRecord::factory()->create();

    expect($record->toArray())->toHaveKey('document_id');
});

test('it casts status to the enum', function(){
    $record = DecisionRecord::factory()->decided()->create();

    expect($record->status)->toBe(App\Enums\DecisionStatus::Decided);
});

test('it deletes its options when the record is deleted', function(){
    $record = DecisionRecord::factory()->create();
    DecisionOption::factory()->count(2)->create(['decision_record_id' => $record->id]);

    $record->delete();

    expect(DecisionOption::count())->toBe(0);
});

test('it resolves links in both directions', function(){
    $source = DecisionRecord::factory()->create();
    $target = DecisionRecord::factory()->create();

    DecisionLink::factory()->create([
        'source_id' => $source->id,
        'target_id' => $target->id,
        'relationship_type' => DecisionRelationshipType::Supersedes,
    ]);

    expect($source->outgoingLinks)->toHaveCount(1)
        ->and($source->incomingLinks)->toHaveCount(0)
        ->and($target->incomingLinks)->toHaveCount(1)
        ->and($target->incomingLinks->first()->source->is($source))->toBeTrue();
});
