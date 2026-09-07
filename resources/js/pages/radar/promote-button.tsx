import { Form } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import { promote } from '@/routes/radar';
import type { RadarItem } from './types';

/**
 * Raises a vetting item from a radar item and links the two, which is the step
 * the radar exists to feed.
 */
export default function PromoteButton({ item }: { item: RadarItem }) {
    const { canWrite } = usePermissions();

    if (item.promoted) {
        return <Badge variant="outline">In the vetting log</Badge>;
    }

    if (!canWrite) {
        return null;
    }

    return (
        <Form {...promote.form(item.id)} options={{ preserveScroll: true }}>
            <Button type="submit" variant="outline" size="sm">
                <ArrowUpRight /> Vet this
            </Button>
        </Form>
    );
}
