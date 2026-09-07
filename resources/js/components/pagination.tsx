import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

/**
 * Previous/next plus a count. Deliberately not a numbered page strip: with a
 * feed queue you move through it, you do not jump to page 27.
 */
export default function Pagination<T>({ page }: { page: Paginated<T> }) {
    if (page.total === 0) {
        return null;
    }

    return (
        <div className="flex items-center justify-between gap-4">
            <p className="text-sm text-muted-foreground">
                Showing {page.from}–{page.to} of {page.total}
            </p>

            {page.last_page > 1 && (
                <div className="flex items-center gap-2">
                    <Button
                        asChild={!!page.prev_page_url}
                        variant="outline"
                        size="sm"
                        disabled={!page.prev_page_url}
                    >
                        {page.prev_page_url ? (
                            <Link href={page.prev_page_url} preserveScroll>
                                <ChevronLeft /> Previous
                            </Link>
                        ) : (
                            <span>
                                <ChevronLeft /> Previous
                            </span>
                        )}
                    </Button>

                    <span className="text-sm text-muted-foreground">
                        {page.current_page} / {page.last_page}
                    </span>

                    <Button
                        asChild={!!page.next_page_url}
                        variant="outline"
                        size="sm"
                        disabled={!page.next_page_url}
                    >
                        {page.next_page_url ? (
                            <Link href={page.next_page_url} preserveScroll>
                                Next <ChevronRight />
                            </Link>
                        ) : (
                            <span>
                                Next <ChevronRight />
                            </span>
                        )}
                    </Button>
                </div>
            )}
        </div>
    );
}
