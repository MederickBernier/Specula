import { Badge } from '@/components/ui/badge';

/**
 * A ring says what you would choose today; a status says what is running. They
 * are shown together because the interesting cases are where they disagree.
 */
const ringVariant: Record<
    string,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    adopt: 'default',
    trial: 'secondary',
    assess: 'outline',
    hold: 'destructive',
};

export function RingBadge({ ring, label }: { ring: string; label: string }) {
    return <Badge variant={ringVariant[ring] ?? 'secondary'}>{label}</Badge>;
}

export function StatusBadge({
    status,
    label,
}: {
    status: string;
    label: string;
}) {
    if (status === 'current') {
        return null;
    }

    return <Badge variant="outline">{label}</Badge>;
}
