<?php

namespace Database\Seeders;

use App\Enums\ConfidenceLevel;
use App\Enums\DecisionRelationshipType;
use App\Enums\DecisionStatus;
use App\Enums\ItemLinkType;
use App\Enums\PrototypeStatus;
use App\Enums\SecurityNoteSource;
use App\Enums\SecurityNoteStatus;
use App\Enums\SecurityRoutedTo;
use App\Enums\SecuritySeverity;
use App\Enums\TriageStatus;
use App\Enums\VettingSourceType;
use App\Enums\VettingStatus;
use App\Models\DecisionLink;
use App\Models\DecisionOption;
use App\Models\DecisionRecord;
use App\Models\FeedSource;
use App\Models\ItemLink;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\Prototype;
use App\Models\RadarItem;
use App\Models\SecurityNote;
use App\Models\VettingItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

/**
 * Demonstration data, so the app can be looked at with something in it.
 *
 * Not part of DatabaseSeeder: this is run on purpose, never as a side effect of
 * setting an instance up.
 *
 *     php artisan db:seed --class=DemoDataSeeder
 *
 * To be rid of it, delete this file. Nothing else refers to it, and it refuses
 * to run in production, so it can be left in place until then without risk.
 */
class DemoDataSeeder extends Seeder
{
    /**
     * Model events are deliberately left on: the derived fields they maintain,
     * such as a link's date and a decision's prefix, are part of what makes
     * this data look like data the app produced rather than data inserted
     * behind its back.
     */
    public function run(): void
    {
        if (App::isProduction()) {
            return;
        }

        // Safe to run twice: the projects are the anchor, so if they are here
        // the rest is too.
        if (Project::query()->whereIn('prefix', ['VNG', 'CRA', 'EDGE'])->exists()) {
            return;
        }

        $vng = $this->visionNextGen();
        $cra = $this->craCompliance();
        $this->edgeFleet();

        $this->triageSomeRadarItems($vng);

        unset($cra);
    }

    /**
     * A project mid-flight: decisions taken, one already superseded, a spike
     * finished and a proposal still open.
     */
    private function visionNextGen(): Project
    {
        $project = Project::create([
            'name' => 'Vision Next Gen',
            'prefix' => 'VNG',
            'description' => "Replacing the desktop client with a web application.\n\n"
                .'The constraint that shapes everything here is that the embedded team '
                .'ships on its own cadence, so anything we choose has to survive a six '
                .'month gap between releases.',
            // Backdated so the timeline opens before the work filed under it.
            'created_at' => CarbonImmutable::now()->subMonths(9),
        ]);

        $stack = DecisionRecord::create([
            'project_id' => $project->id,
            'project_prefix' => 'VNG',
            'category' => 'ARCH',
            'sequence' => 1,
            'title' => 'Client platform for the next generation',
            'status' => DecisionStatus::Superseded,
            'author' => 'Mederick Bernier',
            'deciders' => 'N/A',
            'affects' => 'client, deployment',
            'proposal_context' => 'The current client is a desktop application that has to be '
                ."installed and updated by hand on every machine.\n\nSupport spends more time on "
                .'version drift than on the product itself.',
            'recommendation' => 'Move to a desktop application built with MAUI, sharing the '
                .'existing model layer.',
            'consequences' => 'Keeps the offline story intact, at the cost of an install '
                .'and update path we would still own.',
            'created_at' => CarbonImmutable::now()->subMonths(8),
        ]);

        DecisionOption::create([
            'decision_record_id' => $stack->id,
            'name' => 'MAUI desktop',
            'description' => 'A rewrite of the client, still installed locally.',
            'pros' => "Offline works the way it does today.\nThe team already knows the model layer.",
            'cons' => 'The install and update problem is unchanged.',
            'was_chosen' => true,
        ]);
        DecisionOption::create([
            'decision_record_id' => $stack->id,
            'name' => 'Web application',
            'description' => 'A browser client served from our own infrastructure.',
            'pros' => 'No install, one version live at a time.',
            'cons' => 'Offline needs an answer we do not have yet.',
            'was_chosen' => false,
        ]);

        $web = DecisionRecord::create([
            'project_id' => $project->id,
            'project_prefix' => 'VNG',
            'category' => 'ARCH',
            'sequence' => 2,
            'title' => 'Client platform, revisited after the tooling review',
            'status' => DecisionStatus::Decided,
            'author' => 'Mederick Bernier',
            'deciders' => 'N/A',
            'affects' => 'client, deployment',
            'proposal_context' => 'VNG-ARCH-001 chose a desktop client on the strength of the '
                ."offline requirement.\n\nSix months of support tickets say the offline case is "
                .'rarer than assumed, and the tooling for the desktop path has been the single '
                .'largest source of lost time.',
            'recommendation' => 'Build the client as a web application. Revisit offline only if '
                .'a real customer asks for it twice.',
            'consequences' => 'A JS toolchain to keep current, and a deployment story we now own '
                .'end to end.',
            'conditions_for_revisiting' => 'If two customers independently ask for offline, or if '
                .'a field deployment lands somewhere without reliable connectivity.',
            'next_review_at' => CarbonImmutable::now()->subWeek(),
            'created_at' => CarbonImmutable::now()->subMonths(5),
        ]);

        DecisionOption::create([
            'decision_record_id' => $web->id,
            'name' => 'Server rendered with a sprinkle of JS',
            'description' => 'Traditional pages, progressive enhancement.',
            'pros' => 'Least new machinery.',
            'cons' => 'The editor screens want real client state.',
            'was_chosen' => false,
        ]);
        DecisionOption::create([
            'decision_record_id' => $web->id,
            'name' => 'Inertia with React',
            'description' => 'Server routing, client rendering, one codebase.',
            'pros' => "No separate API to version.\nThe React skills transfer to the next project.",
            'cons' => 'A JS toolchain to keep current.',
            'was_chosen' => true,
        ]);

        DecisionLink::create([
            'source_id' => $web->id,
            'target_id' => $stack->id,
            'relationship_type' => DecisionRelationshipType::Supersedes,
            'impact_summary' => 'The offline requirement that decided VNG-ARCH-001 did not hold '
                .'up against a year of support data.',
        ]);

        $language = DecisionRecord::create([
            'project_id' => $project->id,
            'project_prefix' => 'VNG',
            'category' => 'LANG',
            'sequence' => 1,
            'title' => 'Backend language and framework',
            'status' => DecisionStatus::Decided,
            'author' => 'Mederick Bernier',
            'deciders' => 'N/A',
            'affects' => 'backend, hiring',
            'proposal_context' => 'The backend has to be something one person can carry while '
                .'the embedded team is heads down.',
            'recommendation' => 'Laravel. Twenty years of familiarity beats a marginally better '
                .'fit that costs a month of ramp-up.',
            'created_at' => CarbonImmutable::now()->subMonths(5),
        ]);

        DecisionLink::create([
            'source_id' => $language->id,
            'target_id' => $web->id,
            'relationship_type' => DecisionRelationshipType::Constrains,
            'role_note' => 'Upstream: the client decision fixed the shape of the backend.',
        ]);

        DecisionRecord::create([
            'project_id' => $project->id,
            'project_prefix' => 'VNG',
            'category' => 'INFRA',
            'sequence' => 1,
            'title' => 'Where the application runs',
            'status' => DecisionStatus::Draft,
            'author' => 'Mederick Bernier',
            'deciders' => 'N/A',
            'affects' => 'operations, budget',
            'proposal_context' => 'Single tenant, one small application, a budget that has to '
                .'stay under twenty a month.',
            'recommendation' => '',
            'created_at' => CarbonImmutable::now()->subWeeks(3),
        ]);

        $offline = VettingItem::create([
            'project_id' => $project->id,
            'title' => 'Offline mode for the field engineers',
            'source_type' => VettingSourceType::Stakeholder,
            'source_detail' => 'Raised by the service manager',
            'date_raised' => CarbonImmutable::now()->subMonths(2),
            'proposal_description' => 'Engineers on site sometimes have no signal and want to '
                .'complete a job sheet anyway.',
            'assessment' => 'Real, but rarer than it sounds: three tickets in a year, all at the '
                .'same customer. Wants a prototype before it becomes a commitment.',
            'status' => VettingStatus::NeedsPrototype,
        ]);

        VettingItem::create([
            'project_id' => $project->id,
            'title' => 'Move reporting off the request cycle',
            'source_type' => VettingSourceType::SelfInitiated,
            'source_detail' => 'Noticed during the load review',
            'date_raised' => CarbonImmutable::now()->subMonths(4),
            'proposal_description' => 'The monthly report blocks a request for up to ninety '
                .'seconds.',
            'assessment' => 'Queue it. The work is small and the win is immediate.',
            'status' => VettingStatus::Vetted,
            'date_resolved' => CarbonImmutable::now()->subMonths(4)->addDays(3),
        ]);

        VettingItem::create([
            'project_id' => $project->id,
            'title' => 'Rewrite the model layer in the new stack',
            'source_type' => VettingSourceType::Meeting,
            'source_detail' => 'Architecture sync',
            'date_raised' => CarbonImmutable::now()->subMonths(3),
            'proposal_description' => 'Take the opportunity to rewrite the model layer while the '
                .'client is being replaced.',
            'assessment' => 'Two rewrites at once is one too many.',
            'status' => VettingStatus::Rejected,
            'rejection_reason' => 'The client replacement is already the risky change. The model '
                .'layer works and can wait.',
            'date_resolved' => CarbonImmutable::now()->subMonths(3)->addDays(11),
        ]);

        $spike = $this->finished(Prototype::create([
            'project_id' => $project->id,
            'title' => 'Offline job sheets in the browser',
            'status' => PrototypeStatus::Completed,
            'hypothesis' => 'A service worker plus local storage can hold a job sheet through a '
                .'signal outage and sync it when the connection returns.',
            'test_approach' => 'Built one job sheet screen against a stubbed API, then flew it in '
                .'airplane mode for an afternoon.',
            'result' => 'It works, and the sync conflict case is the hard part rather than the '
                .'storage. Two days of work, not two weeks.',
            'confidence_level' => ConfidenceLevel::Medium,
            'is_reusable' => true,
            'reusability_note' => 'The sync queue lifts out more or less as written.',
            'repo_reference' => 'spike/offline-job-sheets',
            'date_started' => CarbonImmutable::now()->subMonths(2)->addDays(4),
        ]), CarbonImmutable::now()->subMonths(2)->addDays(6));

        ItemLink::create([
            'source_type' => $offline->getMorphClass(),
            'source_id' => $offline->id,
            'target_type' => $spike->getMorphClass(),
            'target_id' => $spike->id,
            'link_type' => ItemLinkType::ResultedIn,
            'note' => 'The proposal asked for a prototype before committing.',
        ]);

        $this->finished(Prototype::create([
            'project_id' => $project->id,
            'title' => 'Server side rendering for first paint',
            'status' => PrototypeStatus::Abandoned,
            'hypothesis' => 'SSR would take a second off the first screen.',
            'test_approach' => 'Stood up the SSR build and measured against the current one.',
            'abandoned_reason' => 'Deprioritised when the load review showed the reporting job '
                .'was the actual complaint.',
            'date_started' => CarbonImmutable::now()->subMonths(4),
        ]), CarbonImmutable::now()->subMonths(4)->addDays(9));

        SecurityNote::create([
            'project_id' => $project->id,
            'title' => 'Session cookie not marked secure in staging',
            'source' => SecurityNoteSource::PersonalChecklist,
            'category' => 'session handling',
            'severity' => SecuritySeverity::Medium,
            'finding' => 'Staging serves over plain HTTP, so the session cookie is sent without '
                .'the secure flag.',
            'is_issue' => true,
            'routed_to' => SecurityRoutedTo::SelfHandled,
            'status' => SecurityNoteStatus::Remediated,
            'date_flagged' => CarbonImmutable::now()->subMonths(3),
            'date_resolved' => CarbonImmutable::now()->subMonths(3)->addDays(2),
        ]);

        ProjectNote::create([
            'project_id' => $project->id,
            'title' => 'Why the offline question keeps coming back',
            'body' => "Every time it is raised it comes from the same customer site.\n\n"
                .'Worth naming that in the next steering meeting rather than re-litigating '
                .'the decision each quarter.',
            'created_at' => CarbonImmutable::now()->subMonths(2),
        ]);

        return $project;
    }

    /**
     * A compliance programme: mostly findings, some still open.
     */
    private function craCompliance(): Project
    {
        $project = Project::create([
            'name' => 'CRA compliance',
            'prefix' => 'CRA',
            'description' => 'Getting the product estate ready for the Cyber Resilience Act, '
                .'and keeping it there.',
            'created_at' => CarbonImmutable::now()->subMonths(6),
        ]);

        SecurityNote::create([
            'project_id' => $project->id,
            'title' => 'Presigned upload URLs do not expire',
            'source' => SecurityNoteSource::AWSInfra,
            'category' => 'IAM',
            'severity' => SecuritySeverity::Critical,
            'finding' => 'The bucket policy grants write to any signed caller and the signature '
                .'has no expiry, so a leaked URL is a permanent write grant.',
            'is_issue' => true,
            'routed_to' => SecurityRoutedTo::WebTeamLead,
            'status' => SecurityNoteStatus::Routed,
            'date_flagged' => CarbonImmutable::now()->subWeeks(2),
        ]);

        SecurityNote::create([
            'project_id' => $project->id,
            'title' => 'Third party SBOM missing for the embedded image',
            'source' => SecurityNoteSource::CRACompliance,
            'category' => 'supply chain',
            'severity' => SecuritySeverity::High,
            'finding' => 'The vendor ships a binary blob with no bill of materials, which the '
                .'CRA will require us to account for.',
            'is_issue' => true,
            'routed_to' => SecurityRoutedTo::EmbeddedTeamLead,
            'status' => SecurityNoteStatus::Deferred,
            'deferral_reason' => 'The vendor has committed to an SBOM in their next release, due '
                .'in the spring.',
            'deferred_until' => CarbonImmutable::now()->subDays(3),
            'date_flagged' => CarbonImmutable::now()->subMonths(4),
        ]);

        SecurityNote::create([
            'project_id' => $project->id,
            'title' => 'Debug endpoint reachable from the internet',
            'source' => SecurityNoteSource::ExternalReport,
            'category' => 'exposure',
            'severity' => SecuritySeverity::High,
            'finding' => 'A reporter found /debug responding on the staging host.',
            'is_issue' => false,
            'non_issue_reason' => 'The host is inside the VPC and the reporter reached it through '
                .'a VPN they already had. Not reachable from the public internet.',
            'routed_to' => SecurityRoutedTo::SelfHandled,
            'status' => SecurityNoteStatus::NonIssue,
            'date_flagged' => CarbonImmutable::now()->subMonths(2),
        ]);

        SecurityNote::create([
            'project_id' => $project->id,
            'title' => 'Dependency with a known deserialisation flaw',
            'source' => SecurityNoteSource::CodeReview,
            'category' => 'dependencies',
            'severity' => SecuritySeverity::Medium,
            'finding' => 'A transitive dependency is three majors behind and carries a known '
                .'deserialisation issue.',
            'is_issue' => true,
            'routed_to' => SecurityRoutedTo::SelfHandled,
            'status' => SecurityNoteStatus::Remediated,
            'date_flagged' => CarbonImmutable::now()->subMonths(5),
            'date_resolved' => CarbonImmutable::now()->subMonths(5)->addDays(21),
        ]);

        VettingItem::create([
            'project_id' => $project->id,
            'title' => 'Adopt a single SBOM tool across both teams',
            'source_type' => VettingSourceType::SelfInitiated,
            'source_detail' => 'CRA readiness review',
            'date_raised' => CarbonImmutable::now()->subMonths(1),
            'proposal_description' => 'Two teams, two build systems, and no shared way to produce '
                .'a bill of materials.',
            'status' => VettingStatus::InProgress,
        ]);

        ProjectNote::create([
            'project_id' => $project->id,
            'title' => 'Standing questions for the vendor',
            'body' => "- When does the SBOM land, precisely?\n- Do they sign their images?\n"
                .'- Who do we contact when they do not answer?',
            'created_at' => CarbonImmutable::now()->subMonths(3),
        ]);

        return $project;
    }

    /**
     * A finished project, archived, so the archive view has something in it.
     */
    private function edgeFleet(): Project
    {
        $project = Project::create([
            'name' => 'Edge fleet rollout',
            'prefix' => 'EDGE',
            'description' => 'The 2025 rollout of the edge boxes. Closed out and archived.',
            'created_at' => CarbonImmutable::now()->subMonths(14),
            'archived_at' => CarbonImmutable::now()->subMonths(2),
        ]);

        DecisionRecord::create([
            'project_id' => $project->id,
            'project_prefix' => 'EDGE',
            'category' => 'INFRA',
            'sequence' => 1,
            'title' => 'How the boxes get their updates',
            'status' => DecisionStatus::Decided,
            'author' => 'Mederick Bernier',
            'deciders' => 'N/A',
            'affects' => 'field operations',
            'proposal_context' => 'Boxes sit behind customer firewalls we do not control.',
            'recommendation' => 'Pull based updates on a schedule, with a signed manifest.',
            'consequences' => 'Updates are slower to land, and nothing has to be opened inbound.',
            'created_at' => CarbonImmutable::now()->subMonths(13),
        ]);

        $this->finished(Prototype::create([
            'project_id' => $project->id,
            'title' => 'Signed manifest verification on device',
            'status' => PrototypeStatus::Completed,
            'hypothesis' => 'The boxes have enough headroom to verify a signature before applying '
                .'an update.',
            'test_approach' => 'Ran verification on the oldest hardware still in the field.',
            'result' => 'Comfortably. Adds about two seconds to an update.',
            'confidence_level' => ConfidenceLevel::High,
            'is_reusable' => false,
            'date_started' => CarbonImmutable::now()->subMonths(13),
        ]), CarbonImmutable::now()->subMonths(13)->addDays(4));

        return $project;
    }

    /**
     * Backdates when a spike stopped.
     *
     * date_completed is derived from the status rather than filled in, which is
     * right for the app and unhelpful for demo data that wants a believable
     * span, so it is written past the guard here rather than opened up.
     */
    private function finished(Prototype $prototype, CarbonImmutable $on): Prototype
    {
        $prototype->forceFill(['date_completed' => $on])->save();

        return $prototype;
    }

    /**
     * Triage a handful of whatever the radar has fetched, so the queue is not
     * uniformly untouched and the practice figures have something to say.
     */
    private function triageSomeRadarItems(Project $project): void
    {
        $items = RadarItem::query()->where('triage_status', TriageStatus::Pending)->limit(14)->get();

        foreach ($items as $index => $item) {
            if ($index % 2 === 0) {
                $item->update(['triage_status' => TriageStatus::Discarded]);

                continue;
            }

            $item->update([
                'triage_status' => TriageStatus::Relevant,
                'relevance_note' => 'Worth keeping an eye on for '.$project->prefix.'.',
            ]);
        }

        $promote = $items->firstWhere('triage_status', TriageStatus::Relevant);

        if ($promote === null) {
            return;
        }

        $raised = VettingItem::create([
            'project_id' => $project->id,
            'title' => $promote->title,
            'source_type' => VettingSourceType::TechRadar,
            'source_detail' => $promote->feedSource instanceof FeedSource
                ? $promote->feedSource->name
                : 'Tech radar',
            'date_raised' => CarbonImmutable::now()->subDays(6),
            'proposal_description' => (string) ($promote->summary ?? $promote->title),
            'status' => VettingStatus::New,
            'external_url' => $promote->url,
        ]);

        ItemLink::create([
            'source_type' => $promote->getMorphClass(),
            'source_id' => $promote->id,
            'target_type' => $raised->getMorphClass(),
            'target_id' => $raised->id,
            'link_type' => ItemLinkType::ResultedIn,
            'note' => 'Raised from the tech radar',
        ]);
    }
}
