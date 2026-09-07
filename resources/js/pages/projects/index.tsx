import { Form, Head, Link, router } from '@inertiajs/react';
import { Archive, ArchiveRestore, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import { archive, create, index, show } from '@/routes/projects';
import type { ProjectSummary } from './types';

export default function ProjectsIndex({
    projects,
    showingArchived,
    archivedCount,
}: {
    projects: ProjectSummary[];
    showingArchived: boolean;
    archivedCount: number;
}) {
    const { canWrite } = usePermissions();

    return (
        <>
            <Head title="Projects" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between">
                    <Heading
                        title="Projects"
                        description="Everything filed under one body of work"
                    />

                    <div className="flex items-center gap-2">
                        {(archivedCount > 0 || showingArchived) && (
                            <Button
                                variant="outline"
                                onClick={() =>
                                    router.get(
                                        index().url,
                                        showingArchived ? {} : { archived: 1 },
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                {showingArchived ? (
                                    <>
                                        <ArchiveRestore /> Show active
                                    </>
                                ) : (
                                    <>
                                        <Archive /> Archived ({archivedCount})
                                    </>
                                )}
                            </Button>
                        )}

                        {canWrite && !showingArchived && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus /> New project
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {projects.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No projects yet.
                    </p>
                ) : (
                    <ul className="grid gap-4 md:grid-cols-2">
                        {projects.map((project) => (
                            <li
                                key={project.id}
                                className="space-y-3 rounded-xl border border-sidebar-border/70 p-4"
                            >
                                <div className="flex items-center gap-2">
                                    <Badge
                                        variant="secondary"
                                        className="font-mono"
                                    >
                                        {project.prefix}
                                    </Badge>
                                    <Link
                                        href={show(project.id)}
                                        className="font-medium hover:underline"
                                    >
                                        {project.name}
                                    </Link>

                                    {canWrite && (
                                        <Form
                                            {...archive.form(project.id)}
                                            options={{ preserveScroll: true }}
                                            className="ml-auto"
                                        >
                                            <input
                                                type="hidden"
                                                name="archived"
                                                value={
                                                    showingArchived ? '0' : '1'
                                                }
                                            />
                                            <Button
                                                type="submit"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={
                                                    showingArchived
                                                        ? `Restore ${project.name}`
                                                        : `Archive ${project.name}`
                                                }
                                            >
                                                {showingArchived ? (
                                                    <ArchiveRestore />
                                                ) : (
                                                    <Archive />
                                                )}
                                            </Button>
                                        </Form>
                                    )}
                                </div>

                                <dl className="grid grid-cols-5 gap-2 text-center text-sm text-muted-foreground">
                                    {[
                                        [
                                            'Decisions',
                                            project.decision_records_count,
                                        ],
                                        [
                                            'Vetting',
                                            project.vetting_items_count,
                                        ],
                                        ['Spikes', project.prototypes_count],
                                        [
                                            'Findings',
                                            project.security_notes_count,
                                        ],
                                        ['Notes', project.notes_count],
                                    ].map(([label, count]) => (
                                        <div key={label as string}>
                                            <dt className="text-xs">{label}</dt>
                                            <dd className="text-lg text-foreground tabular-nums">
                                                {count}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

ProjectsIndex.layout = {
    breadcrumbs: [{ title: 'Projects', href: index() }],
};
