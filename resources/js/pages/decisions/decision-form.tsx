import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import type { SelectOption } from '@/types';
import type { DecisionOption, DecisionRecord } from './types';

type DecisionFormData = {
    project_prefix: string;
    category: string;
    sequence: number | string;
    title: string;
    status: string;
    author: string;
    deciders: string;
    affects: string;
    proposal_context: string;
    recommendation: string;
    consequences: string;
    conditions_for_revisiting: string;
    options: DecisionOption[];
};

const emptyOption: DecisionOption = {
    name: '',
    description: '',
    pros: '',
    cons: '',
    was_chosen: false,
};

function initialData(
    record: DecisionRecord | undefined,
    statuses: SelectOption[],
): DecisionFormData {
    return {
        project_prefix: record?.project_prefix ?? '',
        category: record?.category ?? '',
        sequence: record?.sequence ?? '',
        title: record?.title ?? '',
        status: record?.status ?? statuses[0]?.value ?? '',
        author: record?.author ?? '',
        deciders: record?.deciders ?? '',
        affects: record?.affects ?? '',
        proposal_context: record?.proposal_context ?? '',
        recommendation: record?.recommendation ?? '',
        consequences: record?.consequences ?? '',
        conditions_for_revisiting: record?.conditions_for_revisiting ?? '',
        options:
            record?.options?.map((option) => ({
                ...option,
                description: option.description ?? '',
                pros: option.pros ?? '',
                cons: option.cons ?? '',
            })) ?? [],
    };
}

export default function DecisionForm({
    record,
    statuses,
    submit,
    submitLabel,
}: {
    record?: DecisionRecord;
    statuses: SelectOption[];
    submit: (form: ReturnType<typeof useForm<DecisionFormData>>) => void;
    submitLabel: string;
}) {
    const form = useForm<DecisionFormData>(initialData(record, statuses));
    const { data, setData, processing, errors } = form;

    const updateOption = <K extends keyof DecisionOption>(
        index: number,
        key: K,
        value: DecisionOption[K],
    ) => {
        setData(
            'options',
            data.options.map((option, current) =>
                current === index ? { ...option, [key]: value } : option,
            ),
        );
    };

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                submit(form);
            }}
            className="space-y-8"
        >
            <div className="grid gap-4 md:grid-cols-3">
                <div className="grid gap-2">
                    <Label htmlFor="project_prefix">Project prefix</Label>
                    <Input
                        id="project_prefix"
                        value={data.project_prefix}
                        onChange={(event) =>
                            setData('project_prefix', event.target.value)
                        }
                        placeholder="VNG"
                        required
                    />
                    <InputError message={errors.project_prefix} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="category">Category</Label>
                    <Input
                        id="category"
                        value={data.category}
                        onChange={(event) =>
                            setData('category', event.target.value)
                        }
                        placeholder="ARCH"
                        required
                    />
                    <InputError message={errors.category} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="sequence">Sequence</Label>
                    <Input
                        id="sequence"
                        type="number"
                        min={1}
                        value={data.sequence}
                        onChange={(event) =>
                            setData('sequence', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.sequence} />
                </div>
            </div>

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
                    <Label htmlFor="author">Author</Label>
                    <Input
                        id="author"
                        value={data.author}
                        onChange={(event) =>
                            setData('author', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.author} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="deciders">Deciders</Label>
                    <Input
                        id="deciders"
                        value={data.deciders}
                        onChange={(event) =>
                            setData('deciders', event.target.value)
                        }
                        placeholder="N/A"
                    />
                    <InputError message={errors.deciders} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="affects">Affects</Label>
                    <Input
                        id="affects"
                        value={data.affects}
                        onChange={(event) =>
                            setData('affects', event.target.value)
                        }
                    />
                    <InputError message={errors.affects} />
                </div>
            </div>

            {(
                [
                    ['proposal_context', 'Context', true],
                    ['recommendation', 'Decision / recommendation', true],
                    ['consequences', 'Consequences', false],
                    [
                        'conditions_for_revisiting',
                        'Conditions for revisiting',
                        false,
                    ],
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

            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-lg font-medium">Options considered</h2>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            setData('options', [
                                ...data.options,
                                { ...emptyOption },
                            ])
                        }
                    >
                        <Plus /> Add option
                    </Button>
                </div>

                {data.options.length === 0 && (
                    <p className="text-sm text-muted-foreground">
                        No options recorded yet.
                    </p>
                )}

                {data.options.map((option, index) => (
                    <div
                        key={index}
                        className="space-y-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    >
                        <div className="flex items-start gap-4">
                            <div className="grid flex-1 gap-2">
                                <Label htmlFor={`option-${index}-name`}>
                                    Name
                                </Label>
                                <Input
                                    id={`option-${index}-name`}
                                    value={option.name}
                                    onChange={(event) =>
                                        updateOption(
                                            index,
                                            'name',
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={
                                        errors[
                                            `options.${index}.name` as keyof typeof errors
                                        ]
                                    }
                                />
                            </div>

                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="mt-6"
                                aria-label={`Remove option ${index + 1}`}
                                onClick={() =>
                                    setData(
                                        'options',
                                        data.options.filter(
                                            (_, current) => current !== index,
                                        ),
                                    )
                                }
                            >
                                <Trash2 />
                            </Button>
                        </div>

                        {(
                            [
                                ['description', 'Description'],
                                ['pros', 'Pros'],
                                ['cons', 'Cons'],
                            ] as const
                        ).map(([field, label]) => (
                            <div key={field} className="grid gap-2">
                                <Label htmlFor={`option-${index}-${field}`}>
                                    {label}
                                </Label>
                                <Textarea
                                    id={`option-${index}-${field}`}
                                    value={option[field] ?? ''}
                                    onChange={(event) =>
                                        updateOption(
                                            index,
                                            field,
                                            event.target.value,
                                        )
                                    }
                                    rows={4}
                                />
                            </div>
                        ))}

                        <div className="flex items-center gap-2">
                            <Checkbox
                                id={`option-${index}-chosen`}
                                checked={option.was_chosen}
                                onCheckedChange={(checked) =>
                                    updateOption(
                                        index,
                                        'was_chosen',
                                        checked === true,
                                    )
                                }
                            />
                            <Label htmlFor={`option-${index}-chosen`}>
                                This option was chosen
                            </Label>
                        </div>
                    </div>
                ))}
            </div>

            <Button type="submit" disabled={processing}>
                {submitLabel}
            </Button>
        </form>
    );
}
