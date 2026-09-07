import { Form, router, useForm } from '@inertiajs/react';
import { Bookmark, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/radar';
import { destroy, store } from '@/routes/radar/saved-searches';
import type { SavedSearch } from './types';

type Filters = { status: string | null; q: string; feed: string };

/**
 * Named filter sets, private to the signed-in account. Applying one is just a
 * visit to the radar with its filters, so a saved search is a shortcut rather
 * than a second way of querying.
 */
export default function SavedSearches({
    savedSearches,
    filters,
    isFiltered,
}: {
    savedSearches: SavedSearch[];
    filters: Filters;
    isFiltered: boolean;
}) {
    const form = useForm({
        name: '',
        q: filters.q,
        feed: filters.feed,
        status: filters.status ?? '',
    });

    const { data, setData, processing, errors, reset } = form;

    const isActive = (saved: SavedSearch) =>
        (saved.filters.q ?? '') === filters.q &&
        (saved.filters.feed ?? '') === filters.feed &&
        (saved.filters.status ?? '') === (filters.status ?? '');

    return (
        <div className="space-y-3">
            {savedSearches.length > 0 && (
                <div className="flex flex-wrap items-center gap-2">
                    <Bookmark className="size-4 text-muted-foreground" />

                    {savedSearches.map((saved) => (
                        <span
                            key={saved.id}
                            className="flex items-center gap-1 rounded-full border border-sidebar-border/70 pr-1 pl-3"
                        >
                            <button
                                type="button"
                                className="py-1 text-sm hover:underline"
                                aria-current={
                                    isActive(saved) ? 'true' : undefined
                                }
                                onClick={() =>
                                    router.get(index().url, saved.filters, {
                                        preserveScroll: true,
                                    })
                                }
                            >
                                <span
                                    className={
                                        isActive(saved)
                                            ? 'font-medium'
                                            : undefined
                                    }
                                >
                                    {saved.name}
                                </span>
                            </button>

                            <Form
                                {...destroy.form(saved.id)}
                                options={{ preserveScroll: true }}
                            >
                                <Button
                                    type="submit"
                                    variant="ghost"
                                    size="icon"
                                    className="size-6"
                                    aria-label={`Forget ${saved.name}`}
                                >
                                    <X className="size-3" />
                                </Button>
                            </Form>
                        </span>
                    ))}
                </div>
            )}

            {isFiltered && (
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        // The filters live in props, not in this form, so the
                        // current ones are folded in at submit time.
                        form.transform((current) => ({
                            ...current,
                            q: filters.q,
                            feed: filters.feed,
                            status: filters.status ?? '',
                        }));

                        form.submit(store(), {
                            preserveScroll: true,
                            onSuccess: () => reset('name'),
                        });
                    }}
                    className="flex items-end gap-2"
                >
                    <Input
                        aria-label="Name for this search"
                        placeholder="Name this search"
                        className="w-56"
                        value={data.name}
                        onChange={(event) =>
                            setData('name', event.target.value)
                        }
                        required
                    />
                    <Button
                        type="submit"
                        variant="outline"
                        size="sm"
                        disabled={processing}
                    >
                        <Bookmark /> Save search
                    </Button>
                    {errors.name && (
                        <p className="text-sm text-destructive-foreground">
                            {errors.name}
                        </p>
                    )}
                </form>
            )}
        </div>
    );
}
