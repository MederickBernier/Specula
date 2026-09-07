import { Head } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import Heading from '@/components/heading';
import ItemLinks from '@/components/item-links';
import { MarkdownSection } from '@/components/markdown';
import { index } from '@/routes/radar';
import type { ItemLinkProps, SelectOption } from '@/types';
import PromoteButton from './promote-button';
import TriageForm from './triage-form';
import type { RadarItem } from './types';

type ShowProps = ItemLinkProps & {
    item: RadarItem;
    html: { relevance_note: string | null };
    statuses: SelectOption[];
};

export default function ShowRadarItem({
    item,
    html,
    statuses,
    ...links
}: ShowProps) {
    return (
        <>
            <Head title={item.title} />

            <div className="flex h-full flex-1 flex-col gap-8 p-4">
                <Heading
                    title={item.title}
                    description={item.feed_source?.name ?? 'Added by hand'}
                />

                <dl className="grid gap-3 text-sm sm:grid-cols-3">
                    <div>
                        <dt className="text-muted-foreground">Published</dt>
                        <dd>
                            {item.published_at
                                ? new Date(
                                      item.published_at,
                                  ).toLocaleDateString()
                                : '—'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Fetched</dt>
                        <dd>
                            {new Date(item.fetched_at).toLocaleDateString()}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Triaged</dt>
                        <dd>
                            {item.triaged_at
                                ? new Date(item.triaged_at).toLocaleDateString()
                                : 'Not yet'}
                        </dd>
                    </div>
                </dl>

                <a
                    href={item.url}
                    target="_blank"
                    rel="noreferrer"
                    className="inline-flex w-fit items-center gap-2 text-sm underline"
                >
                    <ExternalLink className="size-4" /> {item.url}
                </a>

                {item.summary && (
                    <section className="space-y-2">
                        <h2 className="text-lg font-medium">Summary</h2>
                        <p className="text-sm">{item.summary}</p>
                    </section>
                )}

                <section className="space-y-3">
                    <h2 className="text-lg font-medium">Triage</h2>
                    <TriageForm item={item} statuses={statuses} />
                    <PromoteButton item={item} />
                </section>

                <MarkdownSection
                    title="Why it is relevant"
                    html={html.relevance_note}
                />

                <ItemLinks {...links} />
            </div>
        </>
    );
}

ShowRadarItem.layout = {
    breadcrumbs: [
        { title: 'Tech radar', href: index() },
        { title: 'Item', href: index() },
    ],
};
