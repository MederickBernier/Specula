import { Form, Head, Link } from '@inertiajs/react';
import { ExternalLink, Pencil, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { MarkdownSection } from '@/components/markdown';
import MarkdownExport from '@/components/markdown-export';
import { RingBadge, StatusBadge } from '@/components/technology-badges';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import { destroy, edit, exportMethod, index } from '@/routes/technologies';
import type { Technology, UsageGroup } from './types';

export default function ShowTechnology({
    technology,
    html,
    usages,
    versions,
    markdown,
}: {
    technology: Technology;
    html: { notes: string | null };
    usages: UsageGroup[];
    markdown: string;
    versions: string[];
}) {
    const { canWrite } = usePermissions();

    return (
        <>
            <Head title={technology.name} />

            <div className="flex h-full flex-1 flex-col gap-8 p-4">
                <div className="flex items-start justify-between gap-4">
                    <div className="space-y-2">
                        <Heading title={technology.name} />
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary">
                                {technology.category}
                            </Badge>
                            <RingBadge
                                ring={technology.ring}
                                label={technology.ring}
                            />
                            <StatusBadge
                                status={technology.status}
                                label={technology.status}
                            />
                            {technology.vendor && (
                                <span className="text-sm text-muted-foreground">
                                    {technology.vendor}
                                </span>
                            )}
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <MarkdownExport
                            downloadUrl={exportMethod(technology.id).url}
                            markdown={markdown}
                        />

                        {canWrite && (
                            <>
                                <Button asChild variant="outline">
                                    <Link href={edit(technology.id)}>
                                        <Pencil /> Edit
                                    </Link>
                                </Button>

                                <Form {...destroy.form(technology.id)}>
                                    <Button type="submit" variant="destructive">
                                        <Trash2 /> Delete
                                    </Button>
                                </Form>
                            </>
                        )}
                    </div>
                </div>

                {technology.homepage_url && (
                    <a
                        href={technology.homepage_url}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex w-fit items-center gap-2 text-sm underline"
                    >
                        <ExternalLink className="size-4" />{' '}
                        {technology.homepage_url}
                    </a>
                )}

                {versions.length > 0 && (
                    <div className="flex flex-wrap items-center gap-2 text-sm">
                        <span className="text-muted-foreground">
                            {versions.length === 1
                                ? 'Running at version'
                                : 'Running at versions'}
                        </span>
                        {versions.map((version) => (
                            <Badge key={version} variant="outline">
                                {version}
                            </Badge>
                        ))}
                    </div>
                )}

                <MarkdownSection title="Notes" html={html.notes} />

                <section className="space-y-4">
                    <h2 className="text-lg font-medium">Where it is used</h2>

                    {usages.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Not recorded anywhere yet.
                        </p>
                    ) : (
                        usages.map((group) => (
                            <div key={group.type} className="space-y-2">
                                <h3 className="text-sm font-medium">
                                    {group.label}
                                </h3>

                                <ul className="divide-y divide-border rounded-xl border border-border">
                                    {group.records.map((record) => (
                                        <li
                                            key={record.id}
                                            className="flex flex-wrap items-center justify-between gap-3 px-4 py-2 text-sm"
                                        >
                                            <span className="flex flex-wrap items-center gap-2">
                                                {record.url ? (
                                                    <Link
                                                        href={record.url}
                                                        className="hover:underline"
                                                    >
                                                        {record.label}
                                                    </Link>
                                                ) : (
                                                    record.label
                                                )}
                                                {record.role && (
                                                    <span className="text-muted-foreground">
                                                        {record.role}
                                                    </span>
                                                )}
                                            </span>

                                            {record.version && (
                                                <Badge variant="outline">
                                                    {record.version}
                                                </Badge>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))
                    )}
                </section>
            </div>
        </>
    );
}

ShowTechnology.layout = {
    breadcrumbs: [
        { title: 'Technologies', href: index() },
        { title: 'Technology', href: index() },
    ],
};
