import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { metrics as metricsRoute } from '@/routes';

type Slice = { label: string; value: number };

type Metrics = {
    decisions: {
        total: number;
        split: Slice[];
        settledShare: number | null;
        perQuarter: Slice[];
    };
    vetting: {
        total: number;
        split: Slice[];
        medianDaysToResolve: number | null;
        rejectedShare: number | null;
    };
    prototypes: {
        total: number;
        split: Slice[];
        medianDaysRunning: number | null;
        abandonedShare: number | null;
        reusableShare: number | null;
    };
    security: {
        total: number;
        split: Slice[];
        open: number;
        medianDaysToRemediate: number | null;
        nonIssueShare: number | null;
        overdueDeferrals: number;
    };
    radar: {
        total: number;
        split: Slice[];
        triagedShare: number | null;
        relevantShare: number | null;
        becameWork: number;
    };
};

/** A single figure. Not a chart: one number has no shape to show. */
function Figure({
    value,
    unit,
    label,
    hint,
}: {
    value: number | null;
    unit?: string;
    label: string;
    hint?: string;
}) {
    return (
        <div className="rounded-xl border border-sidebar-border/70 p-4">
            <p className="text-3xl font-semibold tabular-nums">
                {value === null ? (
                    <span className="text-xl font-normal text-muted-foreground">
                        Not yet
                    </span>
                ) : (
                    <>
                        {value}
                        {unit && (
                            <span className="text-lg font-normal text-muted-foreground">
                                {unit}
                            </span>
                        )}
                    </>
                )}
            </p>
            <p className="mt-1 font-medium">{label}</p>
            {hint && <p className="text-sm text-muted-foreground">{hint}</p>}
        </div>
    );
}

/**
 * A split of one whole, as a single bar.
 *
 * Every segment is named and counted underneath, so identity never rests on
 * colour alone — which the palette check requires, three of these hues sitting
 * under 3:1 against the light surface.
 */
function Split({ slices }: { slices: Slice[] }) {
    const total = slices.reduce((sum, slice) => sum + slice.value, 0);

    if (total === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Nothing recorded yet.
            </p>
        );
    }

    const shown = slices.filter((slice) => slice.value > 0);

    return (
        <div className="space-y-3">
            <div className="flex h-3 gap-[2px] overflow-hidden rounded-full">
                {shown.map((slice, index) => (
                    <span
                        key={slice.label}
                        className="first:rounded-l-full last:rounded-r-full"
                        style={{
                            width: `${(slice.value / total) * 100}%`,
                            background: `var(--series-${(index % 5) + 1})`,
                        }}
                    />
                ))}
            </div>

            <ul className="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                {shown.map((slice, index) => (
                    <li key={slice.label} className="flex items-center gap-2">
                        <span
                            className="size-2 rounded-full"
                            style={{
                                background: `var(--series-${(index % 5) + 1})`,
                            }}
                        />
                        <span>{slice.label}</span>
                        <span className="text-muted-foreground tabular-nums">
                            {slice.value}
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

/** One series over time, so a single hue and no legend: the title names it. */
function Quarters({ data }: { data: Slice[] }) {
    const peak = Math.max(...data.map((point) => point.value), 1);

    return (
        <div className="flex items-end gap-2">
            {data.map((point) => (
                <div
                    key={point.label}
                    className="flex flex-1 flex-col items-center gap-1"
                >
                    <span className="text-sm tabular-nums">{point.value}</span>
                    <div
                        className="w-full rounded-t"
                        style={{
                            height: `${Math.max((point.value / peak) * 88, point.value > 0 ? 4 : 1)}px`,
                            background:
                                point.value > 0
                                    ? 'var(--series-1)'
                                    : 'var(--surface-muted)',
                        }}
                    />
                    <span className="text-xs text-muted-foreground">
                        {point.label}
                    </span>
                </div>
            ))}
        </div>
    );
}

function Section({
    title,
    question,
    children,
}: {
    title: string;
    question: string;
    children: React.ReactNode;
}) {
    return (
        <section className="space-y-4">
            <div>
                <h2 className="text-lg font-medium">{title}</h2>
                <p className="text-sm text-muted-foreground">{question}</p>
            </div>
            {children}
        </section>
    );
}

export default function MetricsPage({ metrics }: { metrics: Metrics }) {
    return (
        <>
            <Head title="Practice" />

            <div className="viz-root flex h-full flex-1 flex-col gap-10 p-4">
                <Heading
                    title="Practice"
                    description="What the record-keeping says about the habit that produced it"
                />

                <Section
                    title="Decisions"
                    question="Am I deciding things, or accumulating drafts?"
                >
                    <div className="grid gap-4 sm:grid-cols-3">
                        <Figure
                            value={metrics.decisions.total}
                            label="Recorded"
                            hint="decision records in total"
                        />
                        <Figure
                            value={metrics.decisions.settledShare}
                            unit="%"
                            label="Settled"
                            hint="decided or superseded"
                        />
                    </div>

                    <Split slices={metrics.decisions.split} />

                    <div className="space-y-2">
                        <h3 className="text-sm font-medium">
                            Decisions recorded per quarter
                        </h3>
                        <Quarters data={metrics.decisions.perQuarter} />
                    </div>
                </Section>

                <Section
                    title="Vetting"
                    question="How long does a proposal sit before it gets an answer?"
                >
                    <div className="grid gap-4 sm:grid-cols-3">
                        <Figure
                            value={metrics.vetting.medianDaysToResolve}
                            unit=" days"
                            label="Median to resolve"
                            hint="middle time from raised to settled"
                        />
                        <Figure
                            value={metrics.vetting.rejectedShare}
                            unit="%"
                            label="Rejected"
                            hint="of the proposals that were settled"
                        />
                        <Figure
                            value={metrics.vetting.total}
                            label="Raised"
                            hint="in total"
                        />
                    </div>

                    <Split slices={metrics.vetting.split} />
                </Section>

                <Section
                    title="Prototypes"
                    question="Do the spikes finish, and does anything survive them?"
                >
                    <div className="grid gap-4 sm:grid-cols-3">
                        <Figure
                            value={metrics.prototypes.medianDaysRunning}
                            unit=" days"
                            label="Median run"
                            hint="from started to finished"
                        />
                        <Figure
                            value={metrics.prototypes.abandonedShare}
                            unit="%"
                            label="Abandoned"
                            hint="of the spikes that stopped"
                        />
                        <Figure
                            value={metrics.prototypes.reusableShare}
                            unit="%"
                            label="Reusable"
                            hint="of the completed ones"
                        />
                    </div>

                    <Split slices={metrics.prototypes.split} />
                </Section>

                <Section
                    title="Security"
                    question="Do findings get fixed, and was the triage call right?"
                >
                    <div className="grid gap-4 sm:grid-cols-4">
                        <Figure
                            value={metrics.security.open}
                            label="Still open"
                            hint="not yet resolved"
                        />
                        <Figure
                            value={metrics.security.medianDaysToRemediate}
                            unit=" days"
                            label="Median to remediate"
                            hint="flagged to fixed"
                        />
                        <Figure
                            value={metrics.security.nonIssueShare}
                            unit="%"
                            label="Non-issues"
                            hint="flagged, then found to be nothing"
                        />
                        <Figure
                            value={metrics.security.overdueDeferrals}
                            label="Deferrals elapsed"
                            hint="put off past their date"
                        />
                    </div>

                    <Split slices={metrics.security.split} />
                </Section>

                <Section
                    title="Radar"
                    question="Am I reading the feeds, or hoarding them?"
                >
                    <div className="grid gap-4 sm:grid-cols-4">
                        <Figure
                            value={metrics.radar.total}
                            label="Fetched"
                            hint="items in total"
                        />
                        <Figure
                            value={metrics.radar.triagedShare}
                            unit="%"
                            label="Triaged"
                            hint="kept or dismissed rather than left"
                        />
                        <Figure
                            value={metrics.radar.relevantShare}
                            unit="%"
                            label="Kept"
                            hint="of the ones triaged"
                        />
                        <Figure
                            value={metrics.radar.becameWork}
                            label="Became work"
                            hint="raised as a proposal or a spike"
                        />
                    </div>

                    <Split slices={metrics.radar.split} />
                </Section>
            </div>
        </>
    );
}

MetricsPage.layout = {
    breadcrumbs: [{ title: 'Practice', href: metricsRoute() }],
};
