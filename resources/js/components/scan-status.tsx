import { Form } from '@inertiajs/react';
import { RefreshCw, TriangleAlert } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import { fetchAll } from '@/routes/radar/feeds';

/**
 * When the feeds were last scanned, and a way to scan them now.
 *
 * The schedule runs hourly, but only if something is running the scheduler,
 * which is quiet when it is not. A radar that has gone still looks the same as
 * a quiet week, so an overdue scan is called out rather than left to be
 * noticed.
 */
export default function ScanStatus({
    lastScanAt,
    overdue = false,
    showButton = true,
}: {
    lastScanAt: string | null;
    overdue?: boolean;
    showButton?: boolean;
}) {
    const { canWrite } = usePermissions();

    return (
        <div className="flex flex-wrap items-center gap-3">
            <p className="text-sm text-muted-foreground">
                {lastScanAt
                    ? `Last scanned ${new Date(lastScanAt).toLocaleString()}`
                    : 'Never scanned'}
            </p>

            {overdue && (
                <p className="flex items-center gap-1 text-sm text-destructive-foreground">
                    <TriangleAlert className="size-4" />
                    Nothing has scanned recently. Check that the scheduler is
                    running.
                </p>
            )}

            {showButton && canWrite && (
                <Form {...fetchAll.form()} options={{ preserveScroll: true }}>
                    {({ processing }) => (
                        <Button
                            type="submit"
                            variant="outline"
                            size="sm"
                            disabled={processing}
                        >
                            <RefreshCw
                                className={
                                    processing ? 'animate-spin' : undefined
                                }
                            />
                            {processing ? 'Scanning…' : 'Scan feeds now'}
                        </Button>
                    )}
                </Form>
            )}
        </div>
    );
}
