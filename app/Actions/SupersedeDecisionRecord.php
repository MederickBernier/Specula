<?php

namespace App\Actions;

use App\Enums\DecisionRelationshipType;
use App\Enums\DecisionStatus;
use App\Models\DecisionLink;
use App\Models\DecisionRecord;
use Illuminate\Support\Facades\DB;

/**
 * Writes the decision that replaces an earlier one, and records the
 * relationship between the two.
 *
 * Supersession comes in two shapes, and the difference matters. A full
 * supersession retires the old record: it is closed and the new one takes over.
 * A partial one names the part it replaces and deliberately leaves the old
 * record standing, unedited, as the snapshot of what was true at the time.
 */
class SupersedeDecisionRecord
{
    /**
     * @param  array{title: string, scope_note: string|null, impact_summary: string|null}  $input
     */
    public function __invoke(DecisionRecord $record, array $input): DecisionRecord
    {
        return DB::transaction(function () use ($record, $input): DecisionRecord {
            $successor = DecisionRecord::create([
                'project_id' => $record->project_id,
                'project_prefix' => $record->project_prefix,
                'category' => $record->category,
                'sequence' => $this->nextSequence($record),
                'title' => $input['title'],
                'status' => DecisionStatus::Draft,
                'author' => $record->author,
                'deciders' => $record->deciders,
                'affects' => $record->affects,
                // The context carries over: a replacement decision is usually
                // answering the same question again. The recommendation does
                // not, because writing it is the whole point.
                'proposal_context' => $record->proposal_context,
                'recommendation' => '',
            ]);

            DecisionLink::create([
                'source_id' => $successor->id,
                'target_id' => $record->id,
                'relationship_type' => DecisionRelationshipType::Supersedes,
                'scope_note' => $input['scope_note'],
                'impact_summary' => $input['impact_summary'],
            ]);

            if ($input['scope_note'] === null) {
                $record->update(['status' => DecisionStatus::Superseded]);
            }

            return $successor;
        });
    }

    /**
     * The next free number in this prefix and category, so the successor sits
     * alongside its predecessor rather than colliding with it.
     */
    private function nextSequence(DecisionRecord $record): int
    {
        $highest = DecisionRecord::query()
            ->where('project_prefix', $record->project_prefix)
            ->where('category', $record->category)
            ->max('sequence');

        return (int) $highest + 1;
    }
}
