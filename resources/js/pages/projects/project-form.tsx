import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import MarkdownField from '@/components/markdown-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Project } from './types';

type ProjectFormData = {
    name: string;
    prefix: string;
    description: string;
};

export default function ProjectForm({
    project,
    submit,
    submitLabel,
}: {
    project?: Project;
    submit: (form: ReturnType<typeof useForm<ProjectFormData>>) => void;
    submitLabel: string;
}) {
    const form = useForm<ProjectFormData>({
        name: project?.name ?? '',
        prefix: project?.prefix ?? '',
        description: project?.description ?? '',
    });

    const { data, setData, processing, errors } = form;

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                submit(form);
            }}
            className="space-y-6"
        >
            <div className="grid gap-4 md:grid-cols-3">
                <div className="grid gap-2 md:col-span-2">
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(event) =>
                            setData('name', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="prefix">Prefix</Label>
                    <Input
                        id="prefix"
                        value={data.prefix}
                        onChange={(event) =>
                            setData('prefix', event.target.value.toUpperCase())
                        }
                        placeholder="VNG"
                        required
                    />
                    <InputError message={errors.prefix} />
                    <p className="text-sm text-muted-foreground">
                        Used for the document id of every decision filed here.
                    </p>
                </div>
            </div>

            <MarkdownField
                id="description"
                label="Description"
                value={data.description}
                onChange={(next) => setData('description', next)}
                error={errors.description}
                rows={6}
            />

            <Button type="submit" disabled={processing}>
                {submitLabel}
            </Button>
        </form>
    );
}
