import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import ProjectField from '@/components/project-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import type { SelectOption } from '@/types';
import type { Prototype } from './types';

type PrototypeFormData = {
    project_id: string;
    title: string;
    status: string;
    hypothesis: string;
    test_approach: string;
    result: string;
    abandoned_reason: string;
    confidence_level: string;
    is_reusable: boolean;
    reusability_note: string;
    repo_reference: string;
    date_started: string;
};

const COMPLETED = 'completed';
const ABANDONED = 'abandoned';

function initialData(
    prototype: Prototype | undefined,
    statuses: SelectOption[],
): PrototypeFormData {
    return {
        project_id: prototype?.project_id ? String(prototype.project_id) : '',
        title: prototype?.title ?? '',
        status: prototype?.status ?? statuses[0]?.value ?? '',
        hypothesis: prototype?.hypothesis ?? '',
        test_approach: prototype?.test_approach ?? '',
        result: prototype?.result ?? '',
        abandoned_reason: prototype?.abandoned_reason ?? '',
        confidence_level: prototype?.confidence_level ?? '',
        is_reusable: prototype?.is_reusable ?? false,
        reusability_note: prototype?.reusability_note ?? '',
        repo_reference: prototype?.repo_reference ?? '',
        date_started:
            prototype?.date_started?.slice(0, 10) ??
            new Date().toISOString().slice(0, 10),
    };
}

export default function PrototypeForm({
    projects,
    prototype,
    statuses,
    confidenceLevels,
    submit,
    submitLabel,
}: {
    prototype?: Prototype;
    projects: SelectOption[];
    statuses: SelectOption[];
    confidenceLevels: SelectOption[];
    submit: (form: ReturnType<typeof useForm<PrototypeFormData>>) => void;
    submitLabel: string;
}) {
    const form = useForm<PrototypeFormData>(initialData(prototype, statuses));
    const { data, setData, processing, errors } = form;

    const isCompleted = data.status === COMPLETED;
    const isAbandoned = data.status === ABANDONED;

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

            <div className="grid gap-4 md:grid-cols-3">
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

                <div className="grid gap-2">
                    <Label htmlFor="date_started">Date started</Label>
                    <Input
                        id="date_started"
                        type="date"
                        value={data.date_started}
                        onChange={(event) =>
                            setData('date_started', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.date_started} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="repo_reference">Repo reference</Label>
                    <Input
                        id="repo_reference"
                        value={data.repo_reference}
                        onChange={(event) =>
                            setData('repo_reference', event.target.value)
                        }
                        placeholder="spike/branch-name"
                    />
                    <InputError message={errors.repo_reference} />
                </div>
            </div>

            {(
                [
                    ['hypothesis', 'Hypothesis', true],
                    ['test_approach', 'Test approach', false],
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

            {isCompleted && (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="result">
                            Result{' '}
                            <span className="text-muted-foreground">
                                (markdown)
                            </span>
                        </Label>
                        <Textarea
                            id="result"
                            value={data.result}
                            onChange={(event) =>
                                setData('result', event.target.value)
                            }
                            rows={8}
                            required
                        />
                        <InputError message={errors.result} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="confidence_level">
                            Confidence in the result
                        </Label>
                        <NativeSelect
                            id="confidence_level"
                            options={confidenceLevels}
                            value={data.confidence_level}
                            onChange={(event) =>
                                setData('confidence_level', event.target.value)
                            }
                        />
                        <InputError message={errors.confidence_level} />
                    </div>

                    <div className="space-y-4">
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="is_reusable"
                                checked={data.is_reusable}
                                onCheckedChange={(checked) =>
                                    setData('is_reusable', checked === true)
                                }
                            />
                            <Label htmlFor="is_reusable">
                                The work is reusable
                            </Label>
                        </div>

                        {data.is_reusable && (
                            <div className="grid gap-2">
                                <Label htmlFor="reusability_note">
                                    What can be reused{' '}
                                    <span className="text-muted-foreground">
                                        (markdown)
                                    </span>
                                </Label>
                                <Textarea
                                    id="reusability_note"
                                    value={data.reusability_note}
                                    onChange={(event) =>
                                        setData(
                                            'reusability_note',
                                            event.target.value,
                                        )
                                    }
                                    rows={4}
                                />
                                <InputError message={errors.reusability_note} />
                            </div>
                        )}
                    </div>
                </>
            )}

            {isAbandoned && (
                <div className="grid gap-2">
                    <Label htmlFor="abandoned_reason">
                        Why it was abandoned{' '}
                        <span className="text-muted-foreground">
                            (markdown)
                        </span>
                    </Label>
                    <Textarea
                        id="abandoned_reason"
                        value={data.abandoned_reason}
                        onChange={(event) =>
                            setData('abandoned_reason', event.target.value)
                        }
                        rows={5}
                        required
                    />
                    <InputError message={errors.abandoned_reason} />
                </div>
            )}

            <Button type="submit" disabled={processing}>
                {submitLabel}
            </Button>
        </form>
    );
}
