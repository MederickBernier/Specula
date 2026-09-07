import { Head, Link, router } from '@inertiajs/react';
import { LayoutGrid, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { RingBadge, StatusBadge } from '@/components/technology-badges';
import { Button } from '@/components/ui/button';
import { NativeSelect } from '@/components/ui/native-select';
import { usePermissions } from '@/hooks/use-permissions';
import { breakdown, create, index, show } from '@/routes/technologies';
import type { SelectOption } from '@/types';
import type { Technology } from './types';

type Filters = { category: string; ring: string; status: string };

export default function TechnologiesIndex({
    technologies,
    filters,
    categories,
    rings,
    statuses,
}: {
    technologies: Technology[];
    filters: Filters;
    categories: SelectOption[];
    rings: SelectOption[];
    statuses: SelectOption[];
}) {
    const { canWrite } = usePermissions();

    const apply = (changes: Partial<Filters>) => {
        const next = { ...filters, ...changes };

        router.get(
            index().url,
            Object.fromEntries(
                Object.entries(next).filter(([, value]) => value !== ''),
            ),
            { preserveScroll: true, preserveState: true, replace: true },
        );
    };

    const labelFor = (options: SelectOption[], value: string) =>
        options.find((option) => option.value === value)?.label ?? value;

    return (
        <>
            <Head title="Technologies" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Technologies"
                        description="What the estate is built from, and what we think of each piece"
                    />

                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <Link href={breakdown()}>
                                <LayoutGrid /> Breakdown
                            </Link>
                        </Button>

                        {canWrite && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus /> Add technology
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <div className="flex flex-wrap gap-3">
                    <NativeSelect
                        aria-label="Filter by category"
                        className="w-48"
                        options={[
                            { value: '', label: 'All categories' },
                            ...categories,
                        ]}
                        value={filters.category}
                        onChange={(event) =>
                            apply({ category: event.target.value })
                        }
                    />
                    <NativeSelect
                        aria-label="Filter by ring"
                        className="w-44"
                        options={[{ value: '', label: 'Any ring' }, ...rings]}
                        value={filters.ring}
                        onChange={(event) =>
                            apply({ ring: event.target.value })
                        }
                    />
                    <NativeSelect
                        aria-label="Filter by status"
                        className="w-44"
                        options={[
                            { value: '', label: 'Any status' },
                            ...statuses,
                        ]}
                        value={filters.status}
                        onChange={(event) =>
                            apply({ status: event.target.value })
                        }
                    />
                </div>

                {technologies.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Nothing in the inventory yet.
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-2 font-medium">
                                        Name
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Category
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Ring
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Used
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {technologies.map((technology) => (
                                    <tr
                                        key={technology.id}
                                        className="border-t border-border"
                                    >
                                        <td className="px-4 py-2">
                                            <Link
                                                href={show(technology.id)}
                                                className="font-medium hover:underline"
                                            >
                                                {technology.name}
                                            </Link>
                                            {technology.vendor && (
                                                <span className="text-muted-foreground">
                                                    {' '}
                                                    · {technology.vendor}
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-2">
                                            {labelFor(
                                                categories,
                                                technology.category,
                                            )}
                                        </td>
                                        <td className="px-4 py-2">
                                            <RingBadge
                                                ring={technology.ring}
                                                label={labelFor(
                                                    rings,
                                                    technology.ring,
                                                )}
                                            />
                                        </td>
                                        <td className="px-4 py-2">
                                            <StatusBadge
                                                status={technology.status}
                                                label={labelFor(
                                                    statuses,
                                                    technology.status,
                                                )}
                                            />
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground tabular-nums">
                                            {technology.usages_count ?? 0}
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

TechnologiesIndex.layout = {
    breadcrumbs: [{ title: 'Technologies', href: index() }],
};
