import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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

            <div className="grid gap-2">
                <Label htmlFor="description">
                    Description{' '}
                    <span className="text-muted-foreground">(markdown)</span>
                </Label>
                <Textarea
                    id="description"
                    value={data.description}
                    onChange={(event) =>
                        setData('description', event.target.value)
                    }
                    rows={6}
                />
                <InputError message={errors.description} />
            </div>

            <Button type="submit" disabled={processing}>
                {submitLabel}
            </Button>
        </form>
    );
}
