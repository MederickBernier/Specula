import { Head, Link, router } from '@inertiajs/react';
import { ExternalLink, Rss, Search, X } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { index, show } from '@/routes/radar';
import { index as feedsIndex } from '@/routes/radar/feeds';
import type { Paginated, SelectOption } from '@/types';
import PromoteButton from './promote-button';
import SavedSearches from './saved-searches';
import TriageForm from './triage-form';
import type { RadarItem, SavedSearch } from './types';

type Filters = { status: string | null; q: string; feed: string };

export default function RadarIndex({
    items,
    statuses,
    feeds,
    filters,
    savedSearches,
    pendingCount,
}: {
    items: Paginated<RadarItem>;
    statuses: SelectOption[];
    feeds: SelectOption[];
    filters: Filters;
    savedSearches: SavedSearch[];
    pendingCount: number;
}) {
    const [search, setSearch] = useState(filters.q);

    const statusFilters = [{ value: '', label: 'Open queue' }, ...statuses];
    const isFiltered = !!filters.q || !!filters.feed || !!filters.status;

    /**
     * Every filter goes through one place so the others survive the change,
     * and paging always starts again from the first page of the new result.
     */
    const applyFilters = (changes: Partial<Filters>) => {
        const next = { ...filters, ...changes };

        router.get(
            index().url,
            {
                ...(next.status ? { status: next.status } : {}),
                ...(next.q ? { q: next.q } : {}),
                ...(next.feed ? { feed: next.feed } : {}),
            },
            { preserveScroll: true, preserveState: true, replace: true },
        );
    };

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

                <div className="flex flex-wrap items-center gap-2">
                    {statusFilters.map((option) => (
                        <Button
                            key={option.value || 'open'}
                            size="sm"
                            variant={
                                (filters.status ?? '') === option.value
                                    ? 'default'
                                    : 'outline'
                            }
                            onClick={() =>
                                applyFilters({ status: option.value })
                            }
                        >
                            {option.label}
                        </Button>
                    ))}
                </div>

                <div className="flex flex-wrap items-end gap-3">
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            applyFilters({ q: search });
                        }}
                        className="flex items-end gap-2"
                    >
                        <Input
                            type="search"
                            aria-label="Search radar items"
                            placeholder="Search title and summary"
                            className="w-64"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                        <Button type="submit" variant="outline" size="sm">
                            <Search /> Search
                        </Button>
                    </form>

                    {feeds.length > 0 && (
                        <NativeSelect
                            aria-label="Filter by feed"
                            className="w-56"
                            options={[
                                { value: '', label: 'All feeds' },
                                ...feeds,
                            ]}
                            value={filters.feed}
                            onChange={(event) =>
                                applyFilters({ feed: event.target.value })
                            }
                        />
                    )}

                    {isFiltered && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                                setSearch('');
                                router.get(
                                    index().url,
                                    {},
                                    { preserveScroll: true },
                                );
                            }}
                        >
                            <X /> Clear
                        </Button>
                    )}
                </div>

                <SavedSearches
                    savedSearches={savedSearches}
                    filters={filters}
                    isFiltered={isFiltered}
                />

                {items.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {isFiltered
                            ? 'Nothing matches those filters.'
                            : 'Nothing here. Add a feed source and fetch it.'}
                    </p>
                ) : (
                    <ul className="space-y-3">
                        {items.data.map((item) => (
                            <li
                                key={item.id}
                                className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 md:flex-row md:items-start md:justify-between"
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

                                <div className="flex flex-col items-start gap-2">
                                    <TriageForm
                                        item={item}
                                        statuses={statuses}
                                    />
                                    <PromoteButton item={item} />
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination page={items} />
            </div>
        </>
    );
}

RadarIndex.layout = {
    breadcrumbs: [{ title: 'Tech radar', href: index() }],
};
