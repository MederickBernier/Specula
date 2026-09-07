import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import { create, index, show } from '@/routes/security-notes';
import type { SelectOption } from '@/types';
import type { SecurityNoteSummary } from './types';

const severityVariants: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    low: 'outline',
    medium: 'secondary',
    high: 'default',
    critical: 'destructive',
};

function labelFor(options: SelectOption[], value: string | null) {
    if (!value) {
        return '—';
    }

    return options.find((option) => option.value === value)?.label ?? value;
}

export default function SecurityIndex({
    notes,
    sources,
    severities,
    routes,
    statuses,
}: {
    notes: SecurityNoteSummary[];
    sources: SelectOption[];
    severities: SelectOption[];
    routes: SelectOption[];
    statuses: SelectOption[];
}) {
    const { canWrite } = usePermissions();

    return (
        <>
            <Head title="Security posture" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between">
                    <Heading
                        title="Security posture"
                        description="Findings, the triage call on each, and where they were routed"
                    />

                    {canWrite && (
                        <Button asChild>
                            <Link href={create()}>
                                <Plus /> New note
                            </Link>
                        </Button>
                    )}
                </div>

                {notes.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No security notes yet.
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-2 font-medium">
                                        Title
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Severity
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Source
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Category
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Routed to
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Flagged
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {notes.map((note) => (
                                    <tr
                                        key={note.id}
                                        className="border-t border-sidebar-border/70 dark:border-sidebar-border"
                                    >
                                        <td className="px-4 py-2">
                                            <Link
                                                href={show(note.id)}
                                                className="hover:underline"
                                            >
                                                {note.title}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-2">
                                            <Badge
                                                variant={
                                                    severityVariants[
                                                        note.severity
                                                    ] ?? 'secondary'
                                                }
                                            >
                                                {labelFor(
                                                    severities,
                                                    note.severity,
                                                )}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-2">
                                            {labelFor(sources, note.source)}
                                        </td>
                                        <td className="px-4 py-2">
                                            {note.category ?? '—'}
                                        </td>
                                        <td className="px-4 py-2">
                                            {labelFor(routes, note.routed_to)}
                                        </td>
                                        <td className="px-4 py-2">
                                            <Badge variant="secondary">
                                                {labelFor(
                                                    statuses,
                                                    note.status,
                                                )}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {new Date(
                                                note.date_flagged,
                                            ).toLocaleDateString()}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}

SecurityIndex.layout = {
    breadcrumbs: [{ title: 'Security posture', href: index() }],
};
