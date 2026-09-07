import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Check } from 'lucide-react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';

type Attention = {
    kind: string;
    label: string;
    url: string;
    why: string;
    at: string | null;
};

type Count = {
    key: string;
    label: string;
    value: number;
    hint: string;
    url: string;
};

type ProjectRow = {
    id: number;
    name: string;
    prefix: string;
    url: string;
    open: { label: string; value: number }[];
};

type Activity = {
    kind: string;
    label: string;
    url: string;
    state: string | null;
    at: string;
};

/**
 * A finding outranks a review, which outranks a feed that stopped answering,
 * so the colour follows the kind rather than the position in the list.
 */
const kindTone: Record<string, string> = {
    Finding: 'border-l-destructive',
    Decision: 'border-l-primary',
    Proposal: 'border-l-primary',
    Feed: 'border-l-muted-foreground',
};

function Section({
    title,
    action,
    children,
}: {
    title: string;
    action?: ReactNode;
    children: ReactNode;
}) {
    return (
        <section className="space-y-3">
            <div className="flex items-center justify-between gap-4">
                <h2 className="font-medium">{title}</h2>
                {action}
            </div>
            {children}
        </section>
    );
}

function relativeDay(value: string) {
    const then = new Date(value);
    const days = Math.round((Date.now() - then.getTime()) / 86_400_000);

    if (days <= 0) {
        return 'today';
    }

    if (days === 1) {
        return 'yesterday';
    }

    if (days < 30) {
        return `${days} days ago`;
    }

    return then.toLocaleDateString();
}

export default function Dashboard({
    attention,
    counts,
    projects,
    activity,
}: {
    attention: Attention[];
    counts: Count[];
    projects: ProjectRow[];
    activity: Activity[];
}) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-8 p-4">
                <header className="space-y-1">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {attention.length === 0
                            ? 'Nothing is waiting on you'
                            : `${attention.length} ${attention.length === 1 ? 'thing wants' : 'things want'} your attention`}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {new Date().toLocaleDateString(undefined, {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'long',
                        })}
                    </p>
                </header>

                {attention.length === 0 ? (
                    <div className="flex items-center gap-3 rounded-xl border border-dashed border-border p-6 text-sm text-muted-foreground">
                        <Check className="size-5" />
                        No overdue reviews, no open severe findings, and every
                        feed is answering.
                    </div>
                ) : (
                    <ul className="space-y-2">
                        {attention.map((item) => (
                            <li
                                key={`${item.kind}-${item.url}-${item.why}`}
                                className={`rounded-lg border border-l-4 border-border bg-card px-4 py-3 ${kindTone[item.kind] ?? 'border-l-border'}`}
                            >
                                <div className="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                                    <Link
                                        href={item.url}
                                        className="font-medium hover:underline"
                                    >
                                        {item.label}
                                    </Link>
                                    <span className="text-xs text-muted-foreground">
                                        {item.kind}
                                    </span>
                                </div>
                                <p className="mt-0.5 text-sm text-muted-foreground">
                                    {item.why}
                                </p>
                            </li>
                        ))}
                    </ul>
                )}

                <Section title="Open work">
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        {counts.map((count) => (
                            <Link
                                key={count.key}
                                href={count.url}
                                className="rounded-xl border border-border bg-card p-4 transition-colors hover:border-ring"
                            >
                                <p className="text-3xl font-semibold tabular-nums">
                                    {count.value}
                                </p>
                                <p className="mt-1 text-sm font-medium">
                                    {count.label}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {count.hint}
                                </p>
                            </Link>
                        ))}
                    </div>
                </Section>

                <div className="grid gap-8 lg:grid-cols-2">
                    <Section title="Projects">
                        {projects.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No active projects.
                            </p>
                        ) : (
                            <ul className="space-y-2">
                                {projects.map((project) => (
                                    <li
                                        key={project.id}
                                        className="rounded-lg border border-border bg-card px-4 py-3"
                                    >
                                        <Link
                                            href={project.url}
                                            className="flex items-center gap-2 font-medium hover:underline"
                                        >
                                            <Badge
                                                variant="secondary"
                                                className="font-mono"
                                            >
                                                {project.prefix}
                                            </Badge>
                                            {project.name}
                                        </Link>

                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {project.open.every(
                                                (part) => part.value === 0,
                                            )
                                                ? 'nothing open'
                                                : project.open
                                                      .filter(
                                                          (part) =>
                                                              part.value > 0,
                                                      )
                                                      .map(
                                                          (part) =>
                                                              `${part.value} ${part.label}`,
                                                      )
                                                      .join(' · ')}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Section>

                    <Section title="Lately">
                        {activity.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Nothing recorded yet.
                            </p>
                        ) : (
                            <ul className="divide-y divide-border rounded-xl border border-border bg-card">
                                {activity.map((event) => (
                                    <li
                                        key={`${event.kind}-${event.url}-${event.at}`}
                                        className="flex items-baseline justify-between gap-3 px-4 py-2 text-sm"
                                    >
                                        <span className="flex min-w-0 items-baseline gap-2">
                                            <span className="shrink-0 text-xs text-muted-foreground">
                                                {event.kind}
                                            </span>
                                            <Link
                                                href={event.url}
                                                className="truncate hover:underline"
                                            >
                                                {event.label}
                                            </Link>
                                        </span>
                                        <span className="shrink-0 text-xs text-muted-foreground">
                                            {relativeDay(event.at)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Section>
                </div>

                <Link
                    href={dashboard()}
                    className="inline-flex w-fit items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    only={['attention', 'counts', 'projects', 'activity']}
                >
                    Refresh <ArrowRight className="size-3" />
                </Link>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
