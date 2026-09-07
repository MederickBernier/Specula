import { Head, Link, router } from '@inertiajs/react';
import { Search as SearchIcon } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { search } from '@/routes';

type Result = {
    label: string;
    url: string;
    snippet: string | null;
    meta: string | null;
};

type Group = {
    module: string;
    total: number;
    results: Result[];
};

/**
 * Marks the matched run inside a snippet, without treating the term as a
 * pattern: it is whatever the person typed.
 */
function Highlight({ text, term }: { text: string; term: string }) {
    const at = text.toLowerCase().indexOf(term.toLowerCase());

    if (term === '' || at === -1) {
        return <>{text}</>;
    }

    return (
        <>
            {text.slice(0, at)}
            <mark className="bg-transparent font-medium text-foreground">
                {text.slice(at, at + term.length)}
            </mark>
            {text.slice(at + term.length)}
        </>
    );
}

export default function Search({
    term,
    groups,
    total,
}: {
    term: string;
    groups: Group[];
    total: number;
}) {
    const [query, setQuery] = useState(term);

    return (
        <>
            <Head title={term ? `Search: ${term}` : 'Search'} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Search"
                    description="Everything written down, in one place"
                />

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(search().url, query ? { q: query } : {}, {
                            preserveState: true,
                        });
                    }}
                    className="flex items-center gap-2"
                >
                    <Input
                        type="search"
                        aria-label="Search everything"
                        placeholder="Did I already weigh this somewhere?"
                        className="max-w-lg"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        autoFocus
                    />
                    <Button type="submit">
                        <SearchIcon /> Search
                    </Button>
                </form>

                {term === '' ? (
                    <p className="text-sm text-muted-foreground">
                        Searches titles and bodies across every module,
                        including the options weighed under a decision.
                    </p>
                ) : total === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Nothing matches “{term}”.
                    </p>
                ) : (
                    <>
                        <p className="text-sm text-muted-foreground">
                            {total} {total === 1 ? 'match' : 'matches'} for “
                            {term}”.
                        </p>

                        <div className="space-y-6">
                            {groups.map((group) => (
                                <section
                                    key={group.module}
                                    className="space-y-2"
                                >
                                    <h2 className="flex items-center gap-2 font-medium">
                                        {group.module}
                                        <Badge variant="secondary">
                                            {group.total}
                                        </Badge>
                                    </h2>

                                    <ul className="divide-y divide-sidebar-border/70 rounded-xl border border-sidebar-border/70">
                                        {group.results.map((result) => (
                                            <li
                                                key={result.url + result.label}
                                                className="px-4 py-3"
                                            >
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <Link
                                                        href={result.url}
                                                        className="font-medium hover:underline"
                                                    >
                                                        <Highlight
                                                            text={result.label}
                                                            term={term}
                                                        />
                                                    </Link>
                                                    {result.meta && (
                                                        <Badge variant="outline">
                                                            {result.meta}
                                                        </Badge>
                                                    )}
                                                </div>

                                                {result.snippet && (
                                                    <p className="mt-1 text-sm text-muted-foreground">
                                                        <Highlight
                                                            text={
                                                                result.snippet
                                                            }
                                                            term={term}
                                                        />
                                                    </p>
                                                )}
                                            </li>
                                        ))}
                                    </ul>

                                    {group.total > group.results.length && (
                                        <p className="text-sm text-muted-foreground">
                                            and{' '}
                                            {group.total - group.results.length}{' '}
                                            more.
                                        </p>
                                    )}
                                </section>
                            ))}
                        </div>
                    </>
                )}
            </div>
        </>
    );
}

Search.layout = {
    breadcrumbs: [{ title: 'Search', href: search() }],
};
