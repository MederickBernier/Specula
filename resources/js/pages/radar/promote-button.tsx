import { Form } from '@inertiajs/react';
import { ArrowUpRight, FlaskConical } from 'lucide-react';
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

    return (
        <div className="flex flex-wrap items-center gap-2">
            {item.promoted ? (
                <Badge variant="outline">In the vetting log</Badge>
            ) : (
                canWrite && (
                    <Form
                        {...promote.form(item.id)}
                        options={{ preserveScroll: true }}
                    >
                        <input type="hidden" name="target" value="vetting" />
                        <Button type="submit" variant="outline" size="sm">
                            <ArrowUpRight /> Vet this
                        </Button>
                    </Form>
                )
            )}

            {item.prototyped ? (
                <Badge variant="outline">Has a prototype</Badge>
            ) : (
                canWrite && (
                    <Form
                        {...promote.form(item.id)}
                        options={{ preserveScroll: true }}
                    >
                        <input type="hidden" name="target" value="prototype" />
                        <Button type="submit" variant="outline" size="sm">
                            <FlaskConical /> Try this
                        </Button>
                    </Form>
                )
            )}
        </div>
    );
}
