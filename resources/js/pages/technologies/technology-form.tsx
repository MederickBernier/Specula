import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import type { SelectOption } from '@/types';
import type { Technology } from './types';

type TechnologyFormData = {
    name: string;
    category: string;
    ring: string;
    status: string;
    vendor: string;
    homepage_url: string;
    notes: string;
};

export default function TechnologyForm({
    technology,
    categories,
    rings,
    statuses,
    submit,
    submitLabel,
}: {
    technology?: Technology;
    categories: SelectOption[];
    rings: SelectOption[];
    statuses: SelectOption[];
    submit: (form: ReturnType<typeof useForm<TechnologyFormData>>) => void;
    submitLabel: string;
}) {
    const form = useForm<TechnologyFormData>({
        name: technology?.name ?? '',
        category: technology?.category ?? categories[0]?.value ?? '',
        ring: technology?.ring ?? 'assess',
        status: technology?.status ?? 'current',
        vendor: technology?.vendor ?? '',
        homepage_url: technology?.homepage_url ?? '',
        notes: technology?.notes ?? '',
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
            <div className="grid gap-4 md:grid-cols-2">
                <div className="grid gap-2">
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
                    <Label htmlFor="category">Category</Label>
                    <NativeSelect
                        id="category"
                        options={categories}
                        value={data.category}
                        onChange={(event) =>
                            setData('category', event.target.value)
                        }
                    />
                    <InputError message={errors.category} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="ring">Ring</Label>
                    <NativeSelect
                        id="ring"
                        options={rings}
                        value={data.ring}
                        onChange={(event) =>
                            setData('ring', event.target.value)
                        }
                    />
                    <p className="text-sm text-muted-foreground">
                        What you would start something new with today.
                    </p>
                    <InputError message={errors.ring} />
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
                    <p className="text-sm text-muted-foreground">
                        What is actually running, which can differ from the
                        ring.
                    </p>
                    <InputError message={errors.status} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="vendor">Vendor</Label>
                    <Input
                        id="vendor"
                        value={data.vendor}
                        onChange={(event) =>
                            setData('vendor', event.target.value)
                        }
                        placeholder="Who is behind it, if that matters"
                    />
                    <InputError message={errors.vendor} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="homepage_url">Homepage</Label>
                    <Input
                        id="homepage_url"
                        type="url"
                        value={data.homepage_url}
                        onChange={(event) =>
                            setData('homepage_url', event.target.value)
                        }
                        placeholder="https://"
                    />
                    <InputError message={errors.homepage_url} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="notes">
                    Notes{' '}
                    <span className="text-muted-foreground">(markdown)</span>
                </Label>
                <Textarea
                    id="notes"
                    value={data.notes}
                    onChange={(event) => setData('notes', event.target.value)}
                    rows={6}
                    placeholder="Why it is here, what it costs, what it would take to leave it"
                />
                <InputError message={errors.notes} />
            </div>

            <Button type="submit" disabled={processing}>
                {submitLabel}
            </Button>
        </form>
    );
}
