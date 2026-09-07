import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import { create, index, show } from '@/routes/prototypes';
import type { SelectOption } from '@/types';
import type { PrototypeSummary } from './types';

function labelFor(options: SelectOption[], value: string | null) {
    if (!value) {
        return '—';
    }

    return options.find((option) => option.value === value)?.label ?? value;
}

export default function PrototypesIndex({
    prototypes,
    statuses,
    confidenceLevels,
}: {
    prototypes: PrototypeSummary[];
    statuses: SelectOption[];
    confidenceLevels: SelectOption[];
}) {
    const { canWrite } = usePermissions();

    return (
        <>
            <Head title="Prototypes" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between">
                    <Heading
                        title="Prototypes"
                        description="Spikes, what they were meant to prove, and how they landed"
                    />

                    {canWrite && (
                        <Button asChild>
                            <Link href={create()}>
                                <Plus /> New prototype
                            </Link>
                        </Button>
                    )}
                </div>

                {prototypes.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No prototypes yet.
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
                                        Status
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Confidence
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Reusable
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Started
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Finished
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {prototypes.map((prototype) => (
                                    <tr
                                        key={prototype.id}
                                        className="border-t border-sidebar-border/70 dark:border-sidebar-border"
                                    >
                                        <td className="px-4 py-2">
                                            <Link
                                                href={show(prototype.id)}
                                                className="hover:underline"
                                            >
                                                {prototype.title}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-2">
                                            <Badge variant="secondary">
                                                {labelFor(
                                                    statuses,
                                                    prototype.status,
                                                )}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-2">
                                            {labelFor(
                                                confidenceLevels,
                                                prototype.confidence_level,
                                            )}
                                        </td>
                                        <td className="px-4 py-2">
                                            {prototype.is_reusable === null
                                                ? '—'
                                                : prototype.is_reusable
                                                  ? 'Yes'
                                                  : 'No'}
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {new Date(
                                                prototype.date_started,
                                            ).toLocaleDateString()}
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {prototype.date_completed
                                                ? new Date(
                                                      prototype.date_completed,
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

PrototypesIndex.layout = {
    breadcrumbs: [{ title: 'Prototypes', href: index() }],
};
