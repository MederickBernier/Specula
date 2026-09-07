import { Form, Head, Link } from '@inertiajs/react';
import { History, Pencil, Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';
import Heading from '@/components/heading';
import { MarkdownSection } from '@/components/markdown';
import MarkdownExport from '@/components/markdown-export';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import { show as decisionShow } from '@/routes/decisions';
import {
    destroy,
    edit,
    exportMethod,
    index,
    timeline,
} from '@/routes/projects';
import { show as prototypeShow } from '@/routes/prototypes';
import { show as securityShow } from '@/routes/security-notes';
import { show as vettingShow } from '@/routes/vetting';
import ProjectNotes from './notes';
import type {
    Project,
    ProjectDecision,
    ProjectNote,
    ProjectRow,
} from './types';

type ShowProps = {
    project: Project;
    html: { description: string | null };
    decisions: ProjectDecision[];
    vettingItems: ProjectRow[];
    prototypes: ProjectRow[];
    securityNotes: ProjectRow[];
    notes: ProjectNote[];
};

function Group({
    title,
    empty,
    children,
}: {
    title: string;
    empty: boolean;
    children: ReactNode;
}) {
    return (
        <section className="space-y-2">
            <h2 className="text-lg font-medium">{title}</h2>
            {empty ? (
                <p className="text-sm text-muted-foreground">
                    Nothing filed here yet.
                </p>
            ) : (
                <ul className="divide-y divide-sidebar-border/70 rounded-xl border border-sidebar-border/70">
                    {children}
                </ul>
            )}
        </section>
    );
}

function Row({
    href,
    label,
    badge,
    meta,
}: {
    href: string;
    label: ReactNode;
    badge?: string;
    meta?: string | null;
}) {
    return (
        <li className="flex items-center justify-between gap-3 px-4 py-2 text-sm">
            <Link href={href} className="hover:underline">
                {label}
            </Link>
            <div className="flex items-center gap-2">
                {badge && <Badge variant="secondary">{badge}</Badge>}
                {meta && <span className="text-muted-foreground">{meta}</span>}
            </div>
        </li>
    );
}

export default function ShowProject({
    project,
    html,
    decisions,
    vettingItems,
    prototypes,
    securityNotes,
    notes,
}: ShowProps) {
    const { canWrite } = usePermissions();

    return (
        <>
            <Head title={project.name} />

            <div className="flex h-full flex-1 flex-col gap-8 p-4">
                <div className="flex items-start justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Badge variant="secondary" className="font-mono">
                            {project.prefix}
                        </Badge>
                        <Heading title={project.name} />
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <Button asChild variant="outline">
                            <Link href={timeline(project.id)}>
                                <History /> Timeline
                            </Link>
                        </Button>

                        <MarkdownExport
                            downloadUrl={exportMethod(project.id).url}
                        />

                        {canWrite && (
                            <>
                                <Button asChild variant="outline">
                                    <Link href={edit(project.id)}>
                                        <Pencil /> Edit
                                    </Link>
                                </Button>

                                <Form {...destroy.form(project.id)}>
                                    <Button type="submit" variant="destructive">
                                        <Trash2 /> Delete
                                    </Button>
                                </Form>
                            </>
                        )}
                    </div>
                </div>

                <MarkdownSection title="About" html={html.description} />

                <Group title="Decision records" empty={decisions.length === 0}>
                    {decisions.map((decision) => (
                        <Row
                            key={decision.id}
                            href={decisionShow(decision.id).url}
                            label={
                                <>
                                    <span className="font-mono">
                                        {decision.document_id}
                                    </span>{' '}
                                    {decision.title}
                                </>
                            }
                            badge={decision.status}
                        />
                    ))}
                </Group>

                <Group title="Vetting log" empty={vettingItems.length === 0}>
                    {vettingItems.map((item) => (
                        <Row
                            key={item.id}
                            href={vettingShow(item.id).url}
                            label={item.title}
                            badge={item.status}
                            meta={
                                item.date_raised
                                    ? new Date(
                                          item.date_raised,
                                      ).toLocaleDateString()
                                    : null
                            }
                        />
                    ))}
                </Group>

                <Group title="Prototypes" empty={prototypes.length === 0}>
                    {prototypes.map((item) => (
                        <Row
                            key={item.id}
                            href={prototypeShow(item.id).url}
                            label={item.title}
                            badge={item.status}
                            meta={
                                item.date_started
                                    ? new Date(
                                          item.date_started,
                                      ).toLocaleDateString()
                                    : null
                            }
                        />
                    ))}
                </Group>

                <Group
                    title="Security findings"
                    empty={securityNotes.length === 0}
                >
                    {securityNotes.map((item) => (
                        <Row
                            key={item.id}
                            href={securityShow(item.id).url}
                            label={item.title}
                            badge={item.severity ?? item.status}
                            meta={
                                item.date_flagged
                                    ? new Date(
                                          item.date_flagged,
                                      ).toLocaleDateString()
                                    : null
                            }
                        />
                    ))}
                </Group>

                <ProjectNotes projectId={project.id} notes={notes} />
            </div>
        </>
    );
}

ShowProject.layout = {
    breadcrumbs: [
        { title: 'Projects', href: index() },
        { title: 'Project', href: index() },
    ],
};
