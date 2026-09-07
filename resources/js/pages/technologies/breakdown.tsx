import { Head, Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import Heading from '@/components/heading';
import { RingBadge, StatusBadge } from '@/components/technology-badges';
import { Badge } from '@/components/ui/badge';
import { breakdown, index } from '@/routes/technologies';
import type { BreakdownCategory, BreakdownProject } from './types';

type Summary = {
    technologies: number;
    usages: number;
    unused: { name: string; url: string }[];
    heldButRunning: { name: string; url: string; total: number }[];
};

const elsewhereLabels: Record<string, string> = {
    prototype: 'prototype',
    decision_record: 'decision',
    security_note: 'finding',
};

/**
 * Use that is not against a project, summarised rather than given a column of
 * its own: a spike or a finding is a place a technology appears, but it is not
 * part of a project's standing stack.
 */
function Elsewhere({ counts }: { counts: Record<string, number> }) {
    const parts = Object.entries(counts).map(([type, count]) => {
        const noun = elsewhereLabels[type] ?? type;

        return `${count} ${noun}${count === 1 ? '' : 's'}`;
    });

    if (parts.length === 0) {
        return <span className="text-muted-foreground">—</span>;
    }

    return (
        <span className="text-sm text-muted-foreground">
            {parts.join(', ')}
        </span>
    );
}

export default function TechnologyBreakdown({
    projects,
    categories,
    summary,
}: {
    projects: BreakdownProject[];
    categories: BreakdownCategory[];
    summary: Summary;
}) {
    return (
        <>
            <Head title="Technology breakdown" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Technology breakdown"
                    description="What runs where, and at which version"
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-xl border border-border p-4">
                        <p className="text-3xl font-semibold tabular-nums">
                            {summary.technologies}
                        </p>
                        <p className="mt-1 font-medium">In the inventory</p>
                    </div>

                    <div className="rounded-xl border border-border p-4">
                        <p className="text-3xl font-semibold tabular-nums">
                            {summary.usages}
                        </p>
                        <p className="mt-1 font-medium">Recorded uses</p>
                    </div>

                    <div className="rounded-xl border border-border p-4">
                        <p className="text-3xl font-semibold tabular-nums">
                            {summary.heldButRunning.length}
                        </p>
                        <p className="mt-1 font-medium">Held, still running</p>
                        <p className="text-sm text-muted-foreground">
                            said not to start with, yet in use
                        </p>
                    </div>

                    <div className="rounded-xl border border-border p-4">
                        <p className="text-3xl font-semibold tabular-nums">
                            {summary.unused.length}
                        </p>
                        <p className="mt-1 font-medium">Not used anywhere</p>
                        <p className="text-sm text-muted-foreground">
                            in the inventory, on nothing
                        </p>
                    </div>
                </div>

                {summary.heldButRunning.length > 0 && (
                    <section className="space-y-2 rounded-xl border border-border p-4">
                        <h2 className="font-medium">Held, but still running</h2>
                        <ul className="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                            {summary.heldButRunning.map((row) => (
                                <li key={row.name}>
                                    <Link
                                        href={row.url}
                                        className="hover:underline"
                                    >
                                        {row.name}
                                    </Link>
                                    <span className="text-muted-foreground">
                                        {' '}
                                        · {row.total}{' '}
                                        {row.total === 1 ? 'place' : 'places'}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                {categories.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Nothing recorded yet. Add a technology, then note where
                        it is used.
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-2 font-medium">
                                        Technology
                                    </th>
                                    {projects.map((project) => (
                                        <th
                                            key={project.id}
                                            className="px-3 py-2 text-center font-medium"
                                        >
                                            <Link
                                                href={project.url}
                                                className="font-mono hover:underline"
                                                title={project.name}
                                            >
                                                {project.prefix}
                                            </Link>
                                            {project.archived && (
                                                <span className="block text-xs font-normal text-muted-foreground">
                                                    archived
                                                </span>
                                            )}
                                        </th>
                                    ))}
                                    <th className="px-4 py-2 font-medium">
                                        Elsewhere
                                    </th>
                                </tr>
                            </thead>

                            {categories.map((category) => (
                                <tbody key={category.category}>
                                    <tr className="border-t border-border">
                                        <th
                                            colSpan={projects.length + 2}
                                            className="bg-muted/30 px-4 py-1.5 text-left text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            {category.label}
                                        </th>
                                    </tr>

                                    {category.technologies.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-t border-border"
                                        >
                                            <td className="px-4 py-2">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <Link
                                                        href={row.url}
                                                        className="font-medium hover:underline"
                                                    >
                                                        {row.name}
                                                    </Link>
                                                    <RingBadge
                                                        ring={row.ring}
                                                        label={row.ringLabel}
                                                    />
                                                    <StatusBadge
                                                        status={row.status}
                                                        label={row.statusLabel}
                                                    />
                                                </div>
                                            </td>

                                            {projects.map((project) => {
                                                const version =
                                                    row.projects[project.id];
                                                const used =
                                                    version !== undefined;

                                                return (
                                                    <td
                                                        key={project.id}
                                                        className="px-3 py-2 text-center"
                                                    >
                                                        {used ? (
                                                            version === '' ? (
                                                                <Check
                                                                    className="mx-auto size-4"
                                                                    aria-label="used"
                                                                />
                                                            ) : (
                                                                <Badge variant="outline">
                                                                    {version}
                                                                </Badge>
                                                            )
                                                        ) : (
                                                            <span className="text-muted-foreground">
                                                                ·
                                                            </span>
                                                        )}
                                                    </td>
                                                );
                                            })}

                                            <td className="px-4 py-2">
                                                <Elsewhere
                                                    counts={row.elsewhere}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            ))}
                        </table>
                    </div>
                )}

                {summary.unused.length > 0 && (
                    <section className="space-y-2">
                        <h2 className="font-medium">Not used anywhere</h2>
                        <ul className="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                            {summary.unused.map((row) => (
                                <li key={row.name}>
                                    <Link
                                        href={row.url}
                                        className="hover:underline"
                                    >
                                        {row.name}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </>
    );
}

TechnologyBreakdown.layout = {
    breadcrumbs: [
        { title: 'Technologies', href: index() },
        { title: 'Breakdown', href: breakdown() },
    ],
};
