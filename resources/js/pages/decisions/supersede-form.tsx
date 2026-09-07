import { useForm } from '@inertiajs/react';
import { Replace } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { supersede } from '@/routes/decisions';
import type { DecisionRecord } from './types';

/**
 * Starts the decision that replaces this one.
 *
 * The scope note is what makes a supersession partial: name a part and the
 * earlier record is left standing as the snapshot of what was true at the
 * time; leave it empty and the earlier record is retired.
 */
export default function SupersedeForm({ record }: { record: DecisionRecord }) {
    const [open, setOpen] = useState(false);

    const form = useForm({
        title: record.title,
        scope_note: '',
        impact_summary: '',
    });

    const { data, setData, processing, errors } = form;

    if (!open) {
        return (
            <Button variant="outline" onClick={() => setOpen(true)}>
                <Replace /> Supersede
            </Button>
        );
    }

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.submit(supersede(record.id));
            }}
            className="w-full space-y-4 rounded-xl border border-sidebar-border/70 p-4"
        >
            <h2 className="font-medium">Supersede {record.document_id}</h2>

            <div className="grid gap-2">
                <Label htmlFor="supersede_title">
                    Title of the replacement
                </Label>
                <Input
                    id="supersede_title"
                    value={data.title}
                    onChange={(event) => setData('title', event.target.value)}
                    required
                />
                <InputError message={errors.title} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="supersede_scope">Which part it replaces</Label>
                <Input
                    id="supersede_scope"
                    value={data.scope_note}
                    onChange={(event) =>
                        setData('scope_note', event.target.value)
                    }
                    placeholder="e.g. deployment model section only"
                />
                <p className="text-sm text-muted-foreground">
                    Leave empty to replace the whole record, which retires it.
                    Name a part and {record.document_id} stays as it is, a
                    snapshot of what was true then.
                </p>
                <InputError message={errors.scope_note} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="supersede_impact">
                    Why it matters{' '}
                    <span className="text-muted-foreground">(markdown)</span>
                </Label>
                <Textarea
                    id="supersede_impact"
                    value={data.impact_summary}
                    onChange={(event) =>
                        setData('impact_summary', event.target.value)
                    }
                    rows={3}
                />
                <InputError message={errors.impact_summary} />
            </div>

            <div className="flex items-center gap-2">
                <Button type="submit" disabled={processing}>
                    Start the replacement
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    onClick={() => setOpen(false)}
                >
                    Cancel
                </Button>
            </div>
        </form>
    );
}
