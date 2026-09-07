import { Head, Link, router } from '@inertiajs/react';
import { ExternalLink, Rss } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index, show } from '@/routes/radar';
import { index as feedsIndex } from '@/routes/radar/feeds';
import type { SelectOption } from '@/types';
import TriageForm from './triage-form';
import type { RadarItem } from './types';

export default function RadarIndex({
    items,
    statuses,
    filter,
    pendingCount,
}: {
    items: RadarItem[];
    statuses: SelectOption[];
    filter: string | null;
    pendingCount: number;
}) {
    const filters = [{ value: '', label: 'Open queue' }, ...statuses];

    return (
        <>
            <Head title="Tech radar" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Tech radar"
                        description={`${pendingCount} item${pendingCount === 1 ? '' : 's'} waiting on triage`}
                    />

                    <Button asChild variant="outline">
                        <Link href={feedsIndex()}>
                            <Rss /> Feed sources
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap gap-2">
                    {filters.map((option) => (
                        <Button
                            key={option.value || 'open'}
                            size="sm"
                            variant={
                                (filter ?? '') === option.value
                                    ? 'default'
                                    : 'outline'
                            }
                            onClick={() =>
                                router.get(
                                    index(
                                        option.value
                                            ? {
                                                  query: {
                                                      status: option.value,
                                                  },
                                              }
                                            : {},
                                    ),
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            {option.label}
                        </Button>
                    ))}
                </div>

                {items.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Nothing here. Add a feed source and fetch it.
                    </p>
                ) : (
                    <ul className="space-y-3">
                        {items.map((item) => (
                            <li
                                key={item.id}
                                className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 md:flex-row md:items-start md:justify-between dark:border-sidebar-border"
                            >
                                <div className="space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Link
                                            href={show(item.id)}
                                            className="font-medium hover:underline"
                                        >
                                            {item.title}
                                        </Link>
                                        {item.is_hidden && (
                                            <Badge variant="outline">
                                                Discarded
                                            </Badge>
                                        )}
                                    </div>

                                    <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                        <span>
                                            {item.feed_source?.name ??
                                                'Added by hand'}
                                        </span>
                                        {item.published_at && (
                                            <span>
                                                ·{' '}
                                                {new Date(
                                                    item.published_at,
                                                ).toLocaleDateString()}
                                            </span>
                                        )}
                                        <a
                                            href={item.url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-1 underline"
                                        >
                                            <ExternalLink className="size-3" />{' '}
                                            Source
                                        </a>
                                    </div>

                                    {item.relevance_note && (
                                        <p className="text-sm">
                                            {item.relevance_note}
                                        </p>
                                    )}
                                </div>

                                <TriageForm item={item} statuses={statuses} />
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

RadarIndex.layout = {
    breadcrumbs: [{ title: 'Tech radar', href: index() }],
};
