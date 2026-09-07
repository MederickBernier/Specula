import { Form, Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, RefreshCw, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { index as radarIndex } from '@/routes/radar';
import {
    destroy,
    fetch as fetchFeed,
    index,
    store,
    update,
} from '@/routes/radar/feeds';
import type { SelectOption } from '@/types';
import type { FeedSource } from '../types';

function FeedForm({
    feed,
    feedTypes,
    onDone,
}: {
    feed?: FeedSource;
    feedTypes: SelectOption[];
    onDone?: () => void;
}) {
    const form = useForm({
        name: feed?.name ?? '',
        url: feed?.url ?? '',
        feed_type: feed?.feed_type ?? feedTypes[0]?.value ?? '',
        is_active: feed?.is_active ?? true,
    });

    const { data, setData, processing, errors, reset } = form;

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.submit(feed ? update(feed.id) : store(), {
                    preserveScroll: true,
                    onSuccess: () => {
                        if (!feed) {
                            reset();
                        }

                        onDone?.();
                    },
                });
            }}
            className="grid gap-4 md:grid-cols-4 md:items-end"
        >
            <div className="grid gap-2">
                <Label htmlFor={`name-${feed?.id ?? 'new'}`}>Name</Label>
                <Input
                    id={`name-${feed?.id ?? 'new'}`}
                    value={data.name}
                    onChange={(event) => setData('name', event.target.value)}
                    required
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`url-${feed?.id ?? 'new'}`}>Feed URL</Label>
                <Input
                    id={`url-${feed?.id ?? 'new'}`}
                    type="url"
                    value={data.url}
                    onChange={(event) => setData('url', event.target.value)}
                    placeholder="https://example.com/feed.xml"
                    required
                />
                <InputError message={errors.url} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`type-${feed?.id ?? 'new'}`}>Type</Label>
                <NativeSelect
                    id={`type-${feed?.id ?? 'new'}`}
                    options={feedTypes}
                    value={data.feed_type}
                    onChange={(event) =>
                        setData('feed_type', event.target.value)
                    }
                />
                <InputError message={errors.feed_type} />
            </div>

            <div className="flex items-center gap-4">
                <div className="flex items-center gap-2">
                    <Checkbox
                        id={`active-${feed?.id ?? 'new'}`}
                        checked={data.is_active}
                        onCheckedChange={(checked) =>
                            setData('is_active', checked === true)
                        }
                    />
                    <Label htmlFor={`active-${feed?.id ?? 'new'}`}>
                        Active
                    </Label>
                </div>

                <Button type="submit" disabled={processing}>
                    {feed ? 'Save' : 'Add feed'}
                </Button>
            </div>
        </form>
    );
}

export default function FeedsIndex({
    feeds,
    feedTypes,
}: {
    feeds: FeedSource[];
    feedTypes: SelectOption[];
}) {
    const [editing, setEditing] = useState<number | null>(null);

    return (
        <>
            <Head title="Feed sources" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Feed sources"
                        description="Pausing a noisy source keeps the items it already produced"
                    />

                    <Button asChild variant="outline">
                        <Link href={radarIndex()}>
                            <ArrowLeft /> Back to radar
                        </Link>
                    </Button>
                </div>

                <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <FeedForm feedTypes={feedTypes} />
                </div>

                {feeds.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No feed sources yet.
                    </p>
                ) : (
                    <ul className="space-y-3">
                        {feeds.map((feed) => (
                            <li
                                key={feed.id}
                                className="space-y-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                            >
                                <div className="flex flex-wrap items-start justify-between gap-4">
                                    <div className="space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="font-medium">
                                                {feed.name}
                                            </span>
                                            {!feed.is_active && (
                                                <Badge variant="outline">
                                                    Paused
                                                </Badge>
                                            )}
                                            <Badge variant="secondary">
                                                {feed.radar_items_count} items
                                            </Badge>
                                        </div>

                                        <p className="font-mono text-sm break-all text-muted-foreground">
                                            {feed.url}
                                        </p>

                                        <p className="text-sm text-muted-foreground">
                                            {feed.last_fetched_at
                                                ? `Last fetched ${new Date(feed.last_fetched_at).toLocaleString()}`
                                                : 'Never fetched'}
                                        </p>

                                        {feed.last_error && (
                                            <p className="text-sm text-destructive">
                                                {feed.last_error}
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <Form
                                            {...fetchFeed.form(feed.id)}
                                            options={{ preserveScroll: true }}
                                        >
                                            <Button
                                                type="submit"
                                                variant="outline"
                                                size="sm"
                                            >
                                                <RefreshCw /> Fetch now
                                            </Button>
                                        </Form>

                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                setEditing(
                                                    editing === feed.id
                                                        ? null
                                                        : feed.id,
                                                )
                                            }
                                        >
                                            {editing === feed.id
                                                ? 'Cancel'
                                                : 'Edit'}
                                        </Button>

                                        <Form
                                            {...destroy.form(feed.id)}
                                            options={{ preserveScroll: true }}
                                        >
                                            <Button
                                                type="submit"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Remove ${feed.name}`}
                                            >
                                                <Trash2 />
                                            </Button>
                                        </Form>
                                    </div>
                                </div>

                                {editing === feed.id && (
                                    <FeedForm
                                        feed={feed}
                                        feedTypes={feedTypes}
                                        onDone={() => setEditing(null)}
                                    />
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

FeedsIndex.layout = {
    breadcrumbs: [
        { title: 'Tech radar', href: radarIndex() },
        { title: 'Feed sources', href: index() },
    ],
};
