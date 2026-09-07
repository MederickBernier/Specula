import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    FlaskConical,
    PauseCircle,
    RssIcon,
} from 'lucide-react';
import type { ReactNode } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import { index as feedsIndex } from '@/routes/radar/feeds';
import { show as securityNote } from '@/routes/security-notes';
import { show as vettingItem } from '@/routes/vetting';

type Stat = {
    key: string;
    label: string;
    value: number;
    hint: string;
    url: string;
};

type Queues = {
    severeFindings: {
        id: number;
        title: string;
        severity: string;
        status: string;
        date_flagged: string;
    }[];
    needsPrototype: { id: number; title: string; date_raised: string }[];
    deferredFindings: { id: number; title: string; date_flagged: string }[];
    staleFeeds: { id: number; name: string; last_error: string }[];
};

function QueueCard({
    title,
    icon,
    empty,
    children,
}: {
    title: string;
    icon: ReactNode;
    empty: boolean;
    children: ReactNode;
}) {
    return (
        <section className="space-y-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <h2 className="flex items-center gap-2 font-medium">
                {icon} {title}
            </h2>

            {empty ? (
                <p className="text-sm text-muted-foreground">
                    Nothing right now.
                </p>
            ) : (
                children
            )}
        </section>
    );
}

export default function Dashboard({
    stats,
    queues,
}: {
    stats: Stat[];
    queues: Queues;
}) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Dashboard"
                    description="What is waiting on you"
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    {stats.map((stat) => (
                        <Link
                            key={stat.key}
                            href={stat.url}
                            className="rounded-xl border border-sidebar-border/70 p-4 transition-colors hover:bg-muted/50 dark:border-sidebar-border"
                        >
                            <p className="text-3xl font-semibold tabular-nums">
                                {stat.value}
                            </p>
                            <p className="mt-1 font-medium">{stat.label}</p>
                            <p className="text-sm text-muted-foreground">
                                {stat.hint}
                            </p>
                        </Link>
                    ))}
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <QueueCard
                        title="Severe findings still open"
                        icon={<AlertTriangle className="size-4" />}
                        empty={queues.severeFindings.length === 0}
                    >
                        <ul className="space-y-2 text-sm">
                            {queues.severeFindings.map((note) => (
                                <li
                                    key={note.id}
                                    className="flex items-center justify-between gap-3"
                                >
                                    <Link
                                        href={securityNote(note.id)}
                                        className="hover:underline"
                                    >
                                        {note.title}
                                    </Link>
                                    <Badge
                                        variant={
                                            note.severity === 'critical'
                                                ? 'destructive'
                                                : 'default'
                                        }
                                    >
                                        {note.severity}
                                    </Badge>
                                </li>
                            ))}
                        </ul>
                    </QueueCard>

                    <QueueCard
                        title="Proposals waiting on a prototype"
                        icon={<FlaskConical className="size-4" />}
                        empty={queues.needsPrototype.length === 0}
                    >
                        <ul className="space-y-2 text-sm">
                            {queues.needsPrototype.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex items-center justify-between gap-3"
                                >
                                    <Link
                                        href={vettingItem(item.id)}
                                        className="hover:underline"
                                    >
                                        {item.title}
                                    </Link>
                                    <span className="text-muted-foreground">
                                        {new Date(
                                            item.date_raised,
                                        ).toLocaleDateString()}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </QueueCard>

                    <QueueCard
                        title="Deferred findings"
                        icon={<PauseCircle className="size-4" />}
                        empty={queues.deferredFindings.length === 0}
                    >
                        <ul className="space-y-2 text-sm">
                            {queues.deferredFindings.map((note) => (
                                <li
                                    key={note.id}
                                    className="flex items-center justify-between gap-3"
                                >
                                    <Link
                                        href={securityNote(note.id)}
                                        className="hover:underline"
                                    >
                                        {note.title}
                                    </Link>
                                    <span className="text-muted-foreground">
                                        {new Date(
                                            note.date_flagged,
                                        ).toLocaleDateString()}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </QueueCard>

                    <QueueCard
                        title="Feeds that failed to fetch"
                        icon={<RssIcon className="size-4" />}
                        empty={queues.staleFeeds.length === 0}
                    >
                        <ul className="space-y-2 text-sm">
                            {queues.staleFeeds.map((feed) => (
                                <li key={feed.id} className="space-y-1">
                                    <Link
                                        href={feedsIndex()}
                                        className="hover:underline"
                                    >
                                        {feed.name}
                                    </Link>
                                    <p className="text-xs text-destructive">
                                        {feed.last_error}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    </QueueCard>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
