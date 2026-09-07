import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index, show } from '@/routes/projects';
import type { Project } from './types';

type TimelineEvent = {
    date: string;
    kind: string;
    event: string;
    label: string;
    url: string | null;
    meta: string | null;
};

/**
 * Events arrive newest first, so grouping in order gives months newest first
 * too, without sorting again.
 */
function groupByMonth(events: TimelineEvent[]) {
    const months: { month: string; events: TimelineEvent[] }[] = [];

    for (const event of events) {
        const month = new Date(event.date).toLocaleDateString(undefined, {
            month: 'long',
            year: 'numeric',
        });

        if (months.at(-1)?.month !== month) {
            months.push({ month, events: [] });
        }

        months.at(-1)?.events.push(event);
    }

    return months;
}

export default function ProjectTimeline({
    project,
    events,
}: {
    project: Project;
    events: TimelineEvent[];
}) {
    const months = groupByMonth(events);

    return (
        <>
            <Head title={`${project.name} timeline`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={`${project.name} timeline`}
                        description="What happened, newest first"
                    />

                    <Button asChild variant="outline">
                        <Link href={show(project.id)}>
                            <ArrowLeft /> Back to project
                        </Link>
                    </Button>
                </div>

                {months.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Nothing has happened yet.
                    </p>
                ) : (
                    <div className="space-y-8">
                        {months.map((month) => (
                            <section key={month.month} className="space-y-3">
                                <h2 className="text-sm font-medium text-muted-foreground">
                                    {month.month}
                                </h2>

                                <ul className="space-y-4 border-l border-sidebar-border/70 pl-4">
                                    {month.events.map((event, index) => (
                                        <li
                                            key={`${event.date}-${event.kind}-${event.label}-${index}`}
                                            className="relative"
                                        >
                                            <span className="absolute top-2 -left-[21px] size-2 rounded-full bg-border" />

                                            <div className="flex flex-wrap items-center gap-2 text-sm">
                                                <span className="text-muted-foreground tabular-nums">
                                                    {new Date(
                                                        event.date,
                                                    ).toLocaleDateString()}
                                                </span>
                                                <Badge variant="secondary">
                                                    {event.kind}
                                                </Badge>
                                                <span className="font-medium">
                                                    {event.event}
                                                </span>
                                                {event.meta && (
                                                    <Badge variant="outline">
                                                        {event.meta}
                                                    </Badge>
                                                )}
                                            </div>

                                            <p className="text-sm">
                                                {event.url ? (
                                                    <Link
                                                        href={event.url}
                                                        className="hover:underline"
                                                    >
                                                        {event.label}
                                                    </Link>
                                                ) : (
                                                    event.label
                                                )}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

ProjectTimeline.layout = {
    breadcrumbs: [
        { title: 'Projects', href: index() },
        { title: 'Timeline', href: index() },
    ],
};
