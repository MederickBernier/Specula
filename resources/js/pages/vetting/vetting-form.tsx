import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import ProjectField from '@/components/project-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import type { SelectOption } from '@/types';
import type { VettingItem } from './types';

type VettingFormData = {
    project_id: string;
    title: string;
    source_type: string;
    source_detail: string;
    date_raised: string;
    proposal_description: string;
    assessment: string;
    status: string;
    rejection_reason: string;
    external_url: string;
};

const REJECTED = 'rejected';

function initialData(
    item: VettingItem | undefined,
    statuses: SelectOption[],
    sourceTypes: SelectOption[],
): VettingFormData {
    return {
        project_id: item?.project_id ? String(item.project_id) : '',
        title: item?.title ?? '',
        source_type: item?.source_type ?? sourceTypes[0]?.value ?? '',
        source_detail: item?.source_detail ?? '',
        date_raised:
            item?.date_raised?.slice(0, 10) ??
            new Date().toISOString().slice(0, 10),
        proposal_description: item?.proposal_description ?? '',
        assessment: item?.assessment ?? '',
        status: item?.status ?? statuses[0]?.value ?? '',
        rejection_reason: item?.rejection_reason ?? '',
        external_url: item?.external_url ?? '',
    };
}

export default function VettingForm({
    projects,
    item,
    statuses,
    sourceTypes,
    submit,
    submitLabel,
}: {
    item?: VettingItem;
    projects: SelectOption[];
    statuses: SelectOption[];
    sourceTypes: SelectOption[];
    submit: (form: ReturnType<typeof useForm<VettingFormData>>) => void;
    submitLabel: string;
}) {
    const form = useForm<VettingFormData>(
        initialData(item, statuses, sourceTypes),
    );
    const { data, setData, processing, errors } = form;

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                submit(form);
            }}
            className="space-y-8"
        >
            <ProjectField
                projects={projects}
                value={data.project_id}
                onChange={(value) => setData('project_id', value)}
                error={errors.project_id}
            />

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
                    <Label htmlFor="source_type">Source</Label>
                    <NativeSelect
                        id="source_type"
                        options={sourceTypes}
                        value={data.source_type}
                        onChange={(event) =>
                            setData('source_type', event.target.value)
                        }
                    />
                    <InputError message={errors.source_type} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="source_detail">Source detail</Label>
                    <Input
                        id="source_detail"
                        value={data.source_detail}
                        onChange={(event) =>
                            setData('source_detail', event.target.value)
                        }
                        placeholder="Who raised it, or which meeting"
                    />
                    <InputError message={errors.source_detail} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="date_raised">Date raised</Label>
                    <Input
                        id="date_raised"
                        type="date"
                        value={data.date_raised}
                        onChange={(event) =>
                            setData('date_raised', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.date_raised} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="status">Status</Label>
                    <NativeSelect
                        id="status"
                        options={statuses}
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

            {(
                [
                    ['proposal_description', 'Proposal', true],
                    ['assessment', 'Assessment', false],
                ] as const
            ).map(([field, label, required]) => (
                <div key={field} className="grid gap-2">
                    <Label htmlFor={field}>
                        {label}{' '}
                        <span className="text-muted-foreground">
                            (markdown)
                        </span>
                    </Label>
                    <Textarea
                        id={field}
                        value={data[field]}
                        onChange={(event) => setData(field, event.target.value)}
                        required={required}
                        rows={8}
                    />
                    <InputError message={errors[field]} />
                </div>
            ))}

            {data.status === REJECTED && (
                <div className="grid gap-2">
                    <Label htmlFor="rejection_reason">
                        Rejection reason{' '}
                        <span className="text-muted-foreground">
                            (markdown)
                        </span>
                    </Label>
                    <Textarea
                        id="rejection_reason"
                        value={data.rejection_reason}
                        onChange={(event) =>
                            setData('rejection_reason', event.target.value)
                        }
                        rows={5}
                        required
                    />
                    <InputError message={errors.rejection_reason} />
                </div>
            )}

            <Button type="submit" disabled={processing}>
                {submitLabel}
            </Button>
        </form>
    );
}
