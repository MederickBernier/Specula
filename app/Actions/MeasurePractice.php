<?php

namespace App\Actions;

use App\Contracts\Labelled;
use App\Enums\DecisionStatus;
use App\Enums\PrototypeStatus;
use App\Enums\SecurityNoteStatus;
use App\Enums\TriageStatus;
use App\Enums\VettingStatus;
use App\Models\DecisionRecord;
use App\Models\ItemLink;
use App\Models\Prototype;
use App\Models\RadarItem;
use App\Models\SecurityNote;
use App\Models\VettingItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Instrumentation of the instrumentation: what the record-keeping says about
 * the habit that produced it.
 *
 * Everything here is counted from dates and statuses already stored. The
 * questions it is built to answer are the uncomfortable ones — am I deciding
 * things or accumulating drafts, do findings actually get fixed, am I reading
 * the feeds I subscribed to.
 */
class MeasurePractice
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'decisions' => $this->decisions(),
            'vetting' => $this->vetting(),
            'prototypes' => $this->prototypes(),
            'security' => $this->security(),
            'radar' => $this->radar(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decisions(): array
    {
        $byStatus = $this->countByStatus(DecisionRecord::query()->get(['status']), DecisionStatus::cases());
        $total = array_sum(array_column($byStatus, 'value'));

        $settled = DecisionRecord::query()
            ->whereIn('status', [DecisionStatus::Decided, DecisionStatus::Superseded])
            ->count();

        return [
            'total' => $total,
            'split' => $byStatus,
            'settledShare' => $this->share($settled, $total),
            'perQuarter' => $this->perQuarter(DecisionRecord::query()->get(['created_at'])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function vetting(): array
    {
        $items = VettingItem::query()->get(['status', 'date_raised', 'date_resolved']);
        $resolved = $items->whereNotNull('date_resolved');

        return [
            'total' => $items->count(),
            'split' => $this->countByStatus($items, VettingStatus::cases()),
            'medianDaysToResolve' => $this->medianDays($resolved, 'date_raised', 'date_resolved'),
            'rejectedShare' => $this->share(
                $items->where('status', VettingStatus::Rejected)->count(),
                $resolved->count(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function prototypes(): array
    {
        $prototypes = Prototype::query()->get([
            'status', 'is_reusable', 'date_started', 'date_completed',
        ]);
        $finished = $prototypes->whereNotNull('date_completed');

        return [
            'total' => $prototypes->count(),
            'split' => $this->countByStatus($prototypes, PrototypeStatus::cases()),
            'medianDaysRunning' => $this->medianDays($finished, 'date_started', 'date_completed'),
            'abandonedShare' => $this->share(
                $prototypes->where('status', PrototypeStatus::Abandoned)->count(),
                $finished->count(),
            ),
            'reusableShare' => $this->share(
                $prototypes->where('is_reusable', true)->count(),
                $prototypes->where('status', PrototypeStatus::Completed)->count(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function security(): array
    {
        $notes = SecurityNote::query()->get([
            'status', 'severity', 'is_issue', 'date_flagged', 'date_resolved',
        ]);

        return [
            'total' => $notes->count(),
            'split' => $this->countByStatus($notes, SecurityNoteStatus::cases()),
            'open' => $notes->whereNull('date_resolved')->count(),
            'medianDaysToRemediate' => $this->medianDays(
                $notes->where('status', SecurityNoteStatus::Remediated),
                'date_flagged',
                'date_resolved',
            ),
            // How often a flagged thing turned out to be nothing: the triage
            // call, measured.
            'nonIssueShare' => $this->share($notes->where('is_issue', false)->count(), $notes->count()),
            'overdueDeferrals' => SecurityNote::query()->deferralElapsed()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function radar(): array
    {
        $items = RadarItem::query()->get(['triage_status']);
        $triaged = $items->where('triage_status', '!=', TriageStatus::Pending)->count();

        return [
            'total' => $items->count(),
            'split' => $this->countByStatus($items, TriageStatus::cases()),
            'triagedShare' => $this->share($triaged, $items->count()),
            'relevantShare' => $this->share(
                $items->where('triage_status', TriageStatus::Relevant)->count(),
                $triaged,
            ),
            // Items that actually became work, which is the only reason to
            // subscribe to anything.
            'becameWork' => ItemLink::query()->where('source_type', 'radar_item')->distinct('source_id')->count('source_id'),
        ];
    }

    /**
     * @param  Collection<int, covariant Model>  $records
     * @param  list<Labelled>  $cases
     * @return list<array{label: string, value: int}>
     */
    private function countByStatus($records, array $cases, string $column = 'status'): array
    {
        $counts = [];

        foreach ($cases as $case) {
            $counts[] = [
                'label' => $case->label(),
                'value' => $records->where($column, $case)->count(),
            ];
        }

        return $counts;
    }

    /**
     * How many were recorded in each of the last eight quarters.
     *
     * @param  Collection<int, DecisionRecord>  $records
     * @return list<array{label: string, value: int}>
     */
    private function perQuarter($records): array
    {
        $quarters = [];
        $cursor = CarbonImmutable::now()->startOfQuarter()->subQuarters(7);

        for ($i = 0; $i < 8; $i++) {
            $start = $cursor->addQuarters($i);
            $end = $start->addQuarter();

            $quarters[] = [
                'label' => 'Q'.$start->quarter.' '.$start->year,
                'value' => $records
                    ->filter(fn (DecisionRecord $record): bool => $record->created_at !== null
                        && $record->created_at >= $start
                        && $record->created_at < $end)
                    ->count(),
            ];
        }

        return $quarters;
    }

    /**
     * The middle number of days between two dates, which says more about a
     * habit than an average that one forgotten item can drag.
     *
     * @param  Collection<int, covariant Model>  $records
     */
    private function medianDays($records, string $from, string $to): ?int
    {
        $spans = $records
            ->filter(fn (Model $record): bool => $record->getAttribute($from) !== null
                && $record->getAttribute($to) !== null)
            ->map(fn (Model $record): int => (int) $record->getAttribute($from)
                ->diffInDays($record->getAttribute($to)))
            ->sort()
            ->values();

        if ($spans->isEmpty()) {
            return null;
        }

        $middle = (int) floor($spans->count() / 2);

        return $spans->count() % 2 === 1
            ? (int) $spans[$middle]
            : (int) round(((int) $spans[$middle - 1] + (int) $spans[$middle]) / 2);
    }

    /**
     * A share of a whole, or null when there is no whole to speak of yet.
     */
    private function share(int $part, int $whole): ?int
    {
        return $whole === 0 ? null : (int) round($part / $whole * 100);
    }
}
