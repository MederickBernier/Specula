import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import MarkdownField from '@/components/markdown-field';
import { Button } from '@/components/ui/button';
import { NativeSelect } from '@/components/ui/native-select';
import { usePermissions } from '@/hooks/use-permissions';
import { triage } from '@/routes/radar';
import type { SelectOption } from '@/types';
import type { RadarItem } from './types';

const RELEVANT = 'relevant';

/**
 * Triage for a single item: keep it with a note, dismiss it, or put it back
 * in the queue. Dismissing hides the item rather than deleting it.
 */
export default function TriageForm({
    item,
    statuses,
}: {
    item: RadarItem;
    statuses: SelectOption[];
}) {
    const { canWrite } = usePermissions();

    const form = useForm({
        triage_status: item.triage_status,
        relevance_note: item.relevance_note ?? '',
    });

    const { data, setData, processing, errors } = form;

    if (!canWrite) {
        return (
            <p className="text-sm text-muted-foreground">
                {statuses.find((status) => status.value === item.triage_status)
                    ?.label ?? item.triage_status}
            </p>
        );
    }

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.submit(triage(item.id), { preserveScroll: true });
            }}
            className="space-y-3"
        >
            <div className="flex flex-wrap items-start gap-2">
                <NativeSelect
                    aria-label={`Triage ${item.title}`}
                    className="w-44"
                    options={statuses}
                    value={data.triage_status}
                    onChange={(event) =>
                        setData('triage_status', event.target.value)
                    }
                />

                <Button type="submit" size="sm" disabled={processing}>
                    Save
                </Button>
            </div>

            {data.triage_status === RELEVANT && (
                <MarkdownField
                    id={`relevance-note-${item.id}`}
                    label="Why this is relevant"
                    value={data.relevance_note}
                    onChange={(next) => setData('relevance_note', next)}
                    error={errors.relevance_note}
                    placeholder="Why this one is worth keeping"
                    rows={3}
                    required
                />
            )}

            <InputError message={errors.triage_status} />
        </form>
    );
}
