import { Form, Head, Link } from '@inertiajs/react';
import { ExternalLink, Pencil, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { MarkdownSection } from '@/components/markdown';
import { Button } from '@/components/ui/button';
import { destroy, edit, index } from '@/routes/vetting';
import type { VettingItem } from './types';

type ShowProps = {
    item: VettingItem;
    html: {
        proposal_description: string | null;
        assessment: string | null;
        rejection_reason: string | null;
    };
};

export default function ShowVettingItem({ item, html }: ShowProps) {
    return (
        <>
            <Head title={item.title} />

            <div className="flex h-full flex-1 flex-col gap-8 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading title={item.title} />

                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <Link href={edit(item.id)}>
                                <Pencil /> Edit
                            </Link>
                        </Button>

                        <Form {...destroy.form(item.id)}>
                            <Button type="submit" variant="destructive">
                                <Trash2 /> Delete
                            </Button>
                        </Form>
                    </div>
                </div>

                <dl className="grid gap-3 text-sm sm:grid-cols-4">
                    <div>
                        <dt className="text-muted-foreground">Status</dt>
                        <dd>{item.status}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Source</dt>
                        <dd>{item.source_detail ?? item.source_type}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Raised</dt>
                        <dd>
                            {new Date(item.date_raised).toLocaleDateString()}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Resolved</dt>
                        <dd>
                            {item.date_resolved
                                ? new Date(
                                      item.date_resolved,
                                  ).toLocaleDateString()
                                : 'Still open'}
                        </dd>
                    </div>
                </dl>

                {item.external_url && (
                    <a
                        href={item.external_url}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex w-fit items-center gap-2 text-sm underline"
                    >
                        <ExternalLink className="size-4" /> {item.external_url}
                    </a>
                )}

                <MarkdownSection
                    title="Proposal"
                    html={html.proposal_description}
                />
                <MarkdownSection title="Assessment" html={html.assessment} />

                {item.rejection_reason && (
                    <MarkdownSection
                        title="Rejection reason"
                        html={html.rejection_reason}
                    />
                )}
            </div>
        </>
    );
}

ShowVettingItem.layout = {
    breadcrumbs: [
        { title: 'Vetting log', href: index() },
        { title: 'Item', href: index() },
    ],
};
