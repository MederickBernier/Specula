import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import type { SelectOption } from '@/types';
import type { SecurityNote } from './types';

type SecurityFormData = {
    title: string;
    source: string;
    category: string;
    severity: string;
    finding: string;
    is_issue: boolean;
    non_issue_reason: string;
    routed_to: string;
    status: string;
    deferral_reason: string;
    date_flagged: string;
    external_url: string;
};

const DEFERRED = 'deferred';

export type SecurityFormOptions = {
    sources: SelectOption[];
    severities: SelectOption[];
    routes: SelectOption[];
    statuses: SelectOption[];
};

function initialData(
    note: SecurityNote | undefined,
    { sources, severities, routes, statuses }: SecurityFormOptions,
): SecurityFormData {
    return {
        title: note?.title ?? '',
        source: note?.source ?? sources[0]?.value ?? '',
        category: note?.category ?? '',
        severity: note?.severity ?? severities[0]?.value ?? '',
        finding: note?.finding ?? '',
        is_issue: note?.is_issue ?? true,
        non_issue_reason: note?.non_issue_reason ?? '',
        routed_to: note?.routed_to ?? routes[0]?.value ?? '',
        status: note?.status ?? statuses[0]?.value ?? '',
        deferral_reason: note?.deferral_reason ?? '',
        date_flagged:
            note?.date_flagged?.slice(0, 10) ??
            new Date().toISOString().slice(0, 10),
        external_url: note?.external_url ?? '',
    };
}

export default function SecurityForm({
    note,
    options,
    submit,
    submitLabel,
}: {
    note?: SecurityNote;
    options: SecurityFormOptions;
    submit: (form: ReturnType<typeof useForm<SecurityFormData>>) => void;
    submitLabel: string;
}) {
    const form = useForm<SecurityFormData>(initialData(note, options));
    const { data, setData, processing, errors } = form;

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                submit(form);
            }}
            className="space-y-8"
        >
            <div className="grid gap-2">
                <Label htmlFor="title">Title</Label>
                <Input
                    id="title"
                    value={data.title}
                    onChange={(event) => setData('title', event.target.value)}
                    required
                />
                <InputError message={errors.title} />
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="source">Source</Label>
                    <NativeSelect
                        id="source"
                        options={options.sources}
                        value={data.source}
                        onChange={(event) =>
                            setData('source', event.target.value)
                        }
                    />
                    <InputError message={errors.source} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="category">Category</Label>
                    <Input
                        id="category"
                        value={data.category}
                        onChange={(event) =>
                            setData('category', event.target.value)
                        }
                        placeholder="SSRF, IAM, credential exposure…"
                    />
                    <InputError message={errors.category} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="severity">Severity</Label>
                    <NativeSelect
                        id="severity"
                        options={options.severities}
                        value={data.severity}
                        onChange={(event) =>
                            setData('severity', event.target.value)
                        }
                    />
                    <InputError message={errors.severity} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="date_flagged">Date flagged</Label>
                    <Input
                        id="date_flagged"
                        type="date"
                        value={data.date_flagged}
                        onChange={(event) =>
                            setData('date_flagged', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.date_flagged} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="routed_to">Routed to</Label>
                    <NativeSelect
                        id="routed_to"
                        options={options.routes}
                        value={data.routed_to}
                        onChange={(event) =>
                            setData('routed_to', event.target.value)
                        }
                    />
                    <InputError message={errors.routed_to} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="status">Status</Label>
                    <NativeSelect
                        id="status"
                        options={options.statuses}
                        value={data.status}
                        onChange={(event) =>
                            setData('status', event.target.value)
                        }
                    />
                    <InputError message={errors.status} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="external_url">External link</Label>
                <Input
                    id="external_url"
                    type="url"
                    value={data.external_url}
                    onChange={(event) =>
                        setData('external_url', event.target.value)
                    }
                    placeholder="https://"
                />
                <InputError message={errors.external_url} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="finding">
                    Finding{' '}
                    <span className="text-muted-foreground">(markdown)</span>
                </Label>
                <Textarea
                    id="finding"
                    value={data.finding}
                    onChange={(event) => setData('finding', event.target.value)}
                    rows={8}
                    required
                />
                <InputError message={errors.finding} />
            </div>

            <div className="space-y-4">
                <div className="flex items-center gap-2">
                    <Checkbox
                        id="is_issue"
                        checked={data.is_issue}
                        onCheckedChange={(checked) =>
                            setData('is_issue', checked === true)
                        }
                    />
                    <Label htmlFor="is_issue">This is a real issue</Label>
                </div>

                {!data.is_issue && (
                    <div className="grid gap-2">
                        <Label htmlFor="non_issue_reason">
                            Why it is not an issue{' '}
                            <span className="text-muted-foreground">
                                (markdown)
                            </span>
                        </Label>
                        <Textarea
                            id="non_issue_reason"
                            value={data.non_issue_reason}
                            onChange={(event) =>
                                setData('non_issue_reason', event.target.value)
                            }
                            rows={5}
                            required
                        />
                        <InputError message={errors.non_issue_reason} />
                    </div>
                )}
            </div>

            {data.status === DEFERRED && (
                <div className="grid gap-2">
                    <Label htmlFor="deferral_reason">
                        Why it was deferred{' '}
                        <span className="text-muted-foreground">
                            (markdown)
                        </span>
                    </Label>
                    <Textarea
                        id="deferral_reason"
                        value={data.deferral_reason}
                        onChange={(event) =>
                            setData('deferral_reason', event.target.value)
                        }
                        rows={5}
                        required
                    />
                    <InputError message={errors.deferral_reason} />
                </div>
            )}

            <Button type="submit" disabled={processing}>
                {submitLabel}
            </Button>
        </form>
    );
}
