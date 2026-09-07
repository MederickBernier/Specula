import { Form, Head, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Separator } from '@/components/ui/separator';
import { index, store, update, destroy } from '@/routes/users';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    is_read_only: boolean;
    created_at: string;
};

const accessOptions = [
    { value: 'read-only', label: 'Read-only' },
    { value: 'full', label: 'Full access' },
];

function accessLabel(user: ManagedUser) {
    if (user.is_admin) {
        return 'Administrator';
    }

    return user.is_read_only ? 'Read-only' : 'Full access';
}

export default function Users({ users }: { users: ManagedUser[] }) {
    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        is_read_only: true,
    });

    const { data, setData, processing, errors, reset } = form;

    return (
        <>
            <Head title="Accounts" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Accounts"
                    description="Give someone access without giving them the ability to change anything"
                />

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.submit(store(), {
                            preserveScroll: true,
                            onSuccess: () => reset(),
                        });
                    }}
                    className="space-y-4"
                >
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
                        <Label htmlFor="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(event) =>
                                setData('email', event.target.value)
                            }
                            required
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Password</Label>
                        <PasswordInput
                            id="password"
                            value={data.password}
                            onChange={(event) =>
                                setData('password', event.target.value)
                            }
                            required
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">
                            Confirm password
                        </Label>
                        <PasswordInput
                            id="password_confirmation"
                            value={data.password_confirmation}
                            onChange={(event) =>
                                setData(
                                    'password_confirmation',
                                    event.target.value,
                                )
                            }
                            required
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="access">Access</Label>
                        <NativeSelect
                            id="access"
                            options={accessOptions}
                            value={data.is_read_only ? 'read-only' : 'full'}
                            onChange={(event) =>
                                setData(
                                    'is_read_only',
                                    event.target.value === 'read-only',
                                )
                            }
                        />
                        <p className="text-sm text-muted-foreground">
                            A read-only account can open every module but cannot
                            create, change or delete anything.
                        </p>
                    </div>

                    <Button type="submit" disabled={processing}>
                        Create account
                    </Button>
                </form>

                <Separator />

                <ul className="space-y-3">
                    {users.map((user) => (
                        <li
                            key={user.id}
                            className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-sidebar-border/70 p-3"
                        >
                            <div>
                                <div className="flex items-center gap-2">
                                    <span className="font-medium">
                                        {user.name}
                                    </span>
                                    <Badge
                                        variant={
                                            user.is_admin
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {accessLabel(user)}
                                    </Badge>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {user.email}
                                </p>
                            </div>

                            {!user.is_admin && (
                                <div className="flex items-center gap-2">
                                    <Form
                                        {...update.form(user.id)}
                                        options={{ preserveScroll: true }}
                                    >
                                        <input
                                            type="hidden"
                                            name="is_read_only"
                                            value={
                                                user.is_read_only ? '0' : '1'
                                            }
                                        />
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            size="sm"
                                        >
                                            {user.is_read_only
                                                ? 'Give full access'
                                                : 'Make read-only'}
                                        </Button>
                                    </Form>

                                    <Form
                                        {...destroy.form(user.id)}
                                        options={{ preserveScroll: true }}
                                    >
                                        <Button
                                            type="submit"
                                            variant="ghost"
                                            size="icon"
                                            aria-label={`Remove ${user.name}`}
                                        >
                                            <Trash2 />
                                        </Button>
                                    </Form>
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            </div>
        </>
    );
}

Users.layout = {
    breadcrumbs: [{ title: 'Accounts', href: index() }],
};
