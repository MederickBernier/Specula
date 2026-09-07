import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';
import type { Auth } from '@/types';

/**
 * The only page a signed-out visitor sees.
 *
 * There is nothing to advertise here: accounts are made by an administrator,
 * so this says what the instance is and points at the way in.
 */
export default function Welcome() {
    const { auth, name } = usePage<{ auth?: Auth; name: string }>().props;

    return (
        <>
            <Head title="Welcome" />

            <div className="flex min-h-dvh flex-col items-center justify-center gap-8 bg-background p-8 text-foreground">
                <div className="flex flex-col items-center gap-4 text-center">
                    <div className="flex size-12 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                        <AppLogoIcon className="size-7 fill-current" />
                    </div>

                    <div className="space-y-2">
                        <h1 className="text-2xl font-semibold">{name}</h1>
                        <p className="max-w-sm text-sm text-muted-foreground">
                            Decisions, the proposals behind them, the spikes
                            that tested them, and the findings raised along the
                            way.
                        </p>
                    </div>
                </div>

                <Button asChild>
                    <Link href={auth?.user ? dashboard() : login()}>
                        {auth?.user ? 'Open the dashboard' : 'Sign in'}
                    </Link>
                </Button>
            </div>
        </>
    );
}
