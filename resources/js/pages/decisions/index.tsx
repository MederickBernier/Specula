import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import ProjectFilter from '@/components/project-filter';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import { create, index, show } from '@/routes/decisions';
import type { SelectOption } from '@/types';
import type { DecisionRecordSummary } from './types';

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    under_rework: 'Under rework',
    decided: 'Decided',
    superseded: 'Superseded',
};

export default function DecisionsIndex({
    records,
    projectFilters,
    projectFilter,
}: {
    records: DecisionRecordSummary[];
    projectFilters: SelectOption[];
    projectFilter: string;
}) {
    const { canWrite } = usePermissions();

    return (
        <>
            <Head title="Decision records" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between">
                    <Heading
                        title="Decision records"
                        description="Architecture decisions, their options and their cross-references"
                    />

                    {canWrite && (
                        <Button asChild>
                            <Link href={create()}>
                                <Plus /> New record
                            </Link>
                        </Button>
                    )}
                </div>

                <ProjectFilter
                    url={index().url}
                    options={projectFilters}
                    value={projectFilter}
                />

                {records.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No decision records yet.
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-2 font-medium">
                                        Document
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Title
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Updated
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {records.map((record) => (
                                    <tr
                                        key={record.id}
                                        className="border-t border-sidebar-border/70 dark:border-sidebar-border"
                                    >
                                        <td className="px-4 py-2 font-mono">
                                            <Link
                                                href={show(record.id)}
                                                className="hover:underline"
                                            >
                                                {record.document_id}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-2">
                                            <Link
                                                href={show(record.id)}
                                                className="hover:underline"
                                            >
                                                {record.title}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-2">
                                            <Badge variant="secondary">
                                                {statusLabels[
                                                    record.status ?? ''
                                                ] ?? record.status}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {record.updated_at
                                                ? new Date(
                                                      record.updated_at,
                                                  ).toLocaleDateString()
                                                : '—'}
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

DecisionsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Decision records',
            href: index(),
        },
    ],
};
