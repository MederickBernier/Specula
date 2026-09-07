import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import ProjectFilter from '@/components/project-filter';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import { create, index, show } from '@/routes/vetting';
import type { SelectOption } from '@/types';
import type { VettingItemSummary } from './types';

function labelFor(options: SelectOption[], value: string) {
    return options.find((option) => option.value === value)?.label ?? value;
}

export default function VettingIndex({
    items,
    projectFilters,
    projectFilter,
    statuses,
    sourceTypes,
}: {
    items: VettingItemSummary[];
    projectFilters: SelectOption[];
    projectFilter: string;
    statuses: SelectOption[];
    sourceTypes: SelectOption[];
}) {
    const { canWrite } = usePermissions();

    return (
        <>
            <Head title="Vetting log" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between">
                    <Heading
                        title="Vetting log"
                        description="Proposals from intake through to a verdict"
                    />

                    {canWrite && (
                        <Button asChild>
                            <Link href={create()}>
                                <Plus /> New item
                            </Link>
                        </Button>
                    )}
                </div>

                <ProjectFilter
                    url={index().url}
                    options={projectFilters}
                    value={projectFilter}
                />

                {items.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Nothing in the log yet.
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-sidebar-border/70">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-2 font-medium">
                                        Title
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Source
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Raised
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Resolved
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.map((item) => (
                                    <tr
                                        key={item.id}
                                        className="border-t border-sidebar-border/70"
                                    >
                                        <td className="px-4 py-2">
                                            <Link
                                                href={show(item.id)}
                                                className="hover:underline"
                                            >
                                                {item.title}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-2">
                                            {labelFor(
                                                sourceTypes,
                                                item.source_type,
                                            )}
                                        </td>
                                        <td className="px-4 py-2">
                                            <Badge variant="secondary">
                                                {labelFor(
                                                    statuses,
                                                    item.status,
                                                )}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {new Date(
                                                item.date_raised,
                                            ).toLocaleDateString()}
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {item.date_resolved
                                                ? new Date(
                                                      item.date_resolved,
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

VettingIndex.layout = {
    breadcrumbs: [{ title: 'Vetting log', href: index() }],
};
