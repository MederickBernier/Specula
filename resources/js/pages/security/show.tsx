import { Form, Head, Link } from '@inertiajs/react';
import { ExternalLink, Pencil, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import ItemLinks from '@/components/item-links';
import { MarkdownSection } from '@/components/markdown';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { destroy, edit, index } from '@/routes/security-notes';
import type { ItemLinkProps } from '@/types';
import type { SecurityNote } from './types';

type ShowProps = ItemLinkProps & {
    note: SecurityNote;
    html: {
        finding: string | null;
        non_issue_reason: string | null;
        deferral_reason: string | null;
    };
};

export default function ShowSecurityNote({ note, html, ...links }: ShowProps) {
    return (
        <>
            <Head title={note.title} />

            <div className="flex h-full flex-1 flex-col gap-8 p-4">
                <div className="flex items-start justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Heading title={note.title} />
                        {!note.is_issue && (
                            <Badge variant="outline">Not an issue</Badge>
                        )}
                    </div>

                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <Link href={edit(note.id)}>
                                <Pencil /> Edit
                            </Link>
                        </Button>

                        <Form {...destroy.form(note.id)}>
                            <Button type="submit" variant="destructive">
                                <Trash2 /> Delete
                            </Button>
                        </Form>
                    </div>
                </div>

                <dl className="grid gap-3 text-sm sm:grid-cols-3 lg:grid-cols-6">
                    <div>
                        <dt className="text-muted-foreground">Severity</dt>
                        <dd>{note.severity}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Source</dt>
                        <dd>{note.source}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Category</dt>
                        <dd>{note.category ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Routed to</dt>
                        <dd>{note.routed_to}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Flagged</dt>
                        <dd>
                            {new Date(note.date_flagged).toLocaleDateString()}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Resolved</dt>
                        <dd>
                            {note.date_resolved
                                ? new Date(
                                      note.date_resolved,
                                  ).toLocaleDateString()
                                : 'Open'}
                        </dd>
                    </div>
                </dl>

                {note.external_url && (
                    <a
                        href={note.external_url}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex w-fit items-center gap-2 text-sm underline"
                    >
                        <ExternalLink className="size-4" /> {note.external_url}
                    </a>
                )}

                <MarkdownSection title="Finding" html={html.finding} />

                {!note.is_issue && (
                    <MarkdownSection
                        title="Why it is not an issue"
                        html={html.non_issue_reason}
                    />
                )}

                {note.deferral_reason && (
                    <MarkdownSection
                        title="Why it was deferred"
                        html={html.deferral_reason}
                    />
                )}

                <ItemLinks {...links} />
            </div>
        </>
    );
}

ShowSecurityNote.layout = {
    breadcrumbs: [
        { title: 'Security posture', href: index() },
        { title: 'Note', href: index() },
    ],
};
