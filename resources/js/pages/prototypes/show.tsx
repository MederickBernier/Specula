import { Form, Head, Link } from '@inertiajs/react';
import { GitBranch, Pencil, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import ItemLinks from '@/components/item-links';
import { MarkdownSection } from '@/components/markdown';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import { destroy, edit, index } from '@/routes/prototypes';
import type { ItemLinkProps } from '@/types';
import type { Prototype } from './types';

type ShowProps = ItemLinkProps & {
    prototype: Prototype;
    html: {
        hypothesis: string | null;
        test_approach: string | null;
        result: string | null;
        abandoned_reason: string | null;
        reusability_note: string | null;
    };
};

export default function ShowPrototype({
    prototype,
    html,
    ...links
}: ShowProps) {
    const { canWrite } = usePermissions();

    return (
        <>
            <Head title={prototype.title} />

            <div className="flex h-full flex-1 flex-col gap-8 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading title={prototype.title} />

                    {canWrite && (
                        <div className="flex items-center gap-2">
                            <Button asChild variant="outline">
                                <Link href={edit(prototype.id)}>
                                    <Pencil /> Edit
                                </Link>
                            </Button>

                            <Form {...destroy.form(prototype.id)}>
                                <Button type="submit" variant="destructive">
                                    <Trash2 /> Delete
                                </Button>
                            </Form>
                        </div>
                    )}
                </div>

                <dl className="grid gap-3 text-sm sm:grid-cols-4">
                    <div>
                        <dt className="text-muted-foreground">Status</dt>
                        <dd>{prototype.status}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Confidence</dt>
                        <dd>{prototype.confidence_level ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Started</dt>
                        <dd>
                            {new Date(
                                prototype.date_started,
                            ).toLocaleDateString()}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Finished</dt>
                        <dd>
                            {prototype.date_completed
                                ? new Date(
                                      prototype.date_completed,
                                  ).toLocaleDateString()
                                : 'Still running'}
                        </dd>
                    </div>
                </dl>

                {prototype.repo_reference && (
                    <p className="flex items-center gap-2 text-sm">
                        <GitBranch className="size-4" />
                        <span className="font-mono">
                            {prototype.repo_reference}
                        </span>
                    </p>
                )}

                <MarkdownSection title="Hypothesis" html={html.hypothesis} />
                <MarkdownSection
                    title="Test approach"
                    html={html.test_approach}
                />

                {prototype.abandoned_reason ? (
                    <MarkdownSection
                        title="Why it was abandoned"
                        html={html.abandoned_reason}
                    />
                ) : (
                    <MarkdownSection title="Result" html={html.result} />
                )}

                {prototype.is_reusable && (
                    <section className="space-y-2">
                        <div className="flex items-center gap-2">
                            <h2 className="text-lg font-medium">Reusable</h2>
                            <Badge>Yes</Badge>
                        </div>
                        <MarkdownSection
                            title="What can be reused"
                            html={html.reusability_note}
                        />
                    </section>
                )}

                <ItemLinks {...links} />
            </div>
        </>
    );
}

ShowPrototype.layout = {
    breadcrumbs: [
        { title: 'Prototypes', href: index() },
        { title: 'Prototype', href: index() },
    ],
};
