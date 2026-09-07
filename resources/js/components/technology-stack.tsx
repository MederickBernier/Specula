import { Form, useForm } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { usePermissions } from '@/hooks/use-permissions';
import { destroy, store } from '@/routes/technology-usages';
import type { SelectOption } from '@/types';

export type StackEntry = {
    id: number;
    name: string;
    url: string;
    category: string;
    version: string | null;
    role: string | null;
};

/** The props a record's show page receives so it can present its stack. */
export type TechnologyStackProps = {
    stack: StackEntry[];
    technologyOptions: SelectOption[];
    stackTarget: { type: string; id: number };
};

/**
 * What a record is built with.
 *
 * The version is recorded here rather than against the technology, because two
 * projects running the same thing at different versions is the normal case and
 * the difference is the whole point of asking.
 */
export default function TechnologyStack({
    stack,
    technologyOptions,
    stackTarget,
    title = 'Built with',
}: TechnologyStackProps & { title?: string }) {
    const { canWrite } = usePermissions();

    const form = useForm({
        technology_id: technologyOptions[0]?.value ?? '',
        usable_type: stackTarget.type,
        usable_id: stackTarget.id,
        version: '',
        role: '',
    });

    const { data, setData, processing, errors, reset } = form;

    return (
        <section className="space-y-4">
            <h2 className="text-lg font-medium">{title}</h2>

            {stack.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    Nothing recorded yet.
                </p>
            ) : (
                <ul className="divide-y divide-border rounded-xl border border-border">
                    {stack.map((entry) => (
                        <li
                            key={entry.id}
                            className="flex flex-wrap items-center justify-between gap-3 px-4 py-2 text-sm"
                        >
                            <span className="flex flex-wrap items-center gap-2">
                                <Link
                                    href={entry.url}
                                    className="font-medium hover:underline"
                                >
                                    {entry.name}
                                </Link>
                                {entry.version && (
                                    <Badge variant="outline">
                                        {entry.version}
                                    </Badge>
                                )}
                                {entry.role && (
                                    <span className="text-muted-foreground">
                                        {entry.role}
                                    </span>
                                )}
                            </span>

                            {canWrite && (
                                <Form
                                    {...destroy.form(entry.id)}
                                    options={{ preserveScroll: true }}
                                >
                                    <Button
                                        type="submit"
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Remove ${entry.name}`}
                                    >
                                        <Trash2 />
                                    </Button>
                                </Form>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            {canWrite && technologyOptions.length > 0 && (
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.submit(store(), {
                            preserveScroll: true,
                            onSuccess: () => reset('version', 'role'),
                        });
                    }}
                    className="grid gap-4 rounded-xl border border-border p-4 md:grid-cols-4 md:items-end"
                >
                    <div className="grid gap-2 md:col-span-2">
                        <Label htmlFor="technology_id">Technology</Label>
                        <NativeSelect
                            id="technology_id"
                            options={technologyOptions}
                            value={data.technology_id}
                            onChange={(event) =>
                                setData('technology_id', event.target.value)
                            }
                        />
                        <InputError message={errors.technology_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="version">Version</Label>
                        <Input
                            id="version"
                            value={data.version}
                            onChange={(event) =>
                                setData('version', event.target.value)
                            }
                            placeholder="18"
                        />
                        <InputError message={errors.version} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="role">Role</Label>
                        <Input
                            id="role"
                            value={data.role}
                            onChange={(event) =>
                                setData('role', event.target.value)
                            }
                            placeholder="primary datastore"
                        />
                        <InputError message={errors.role} />
                    </div>

                    <Button
                        type="submit"
                        disabled={processing}
                        className="md:col-span-4 md:w-fit"
                    >
                        Record it
                    </Button>
                </form>
            )}
        </section>
    );
}
