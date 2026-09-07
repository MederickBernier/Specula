import { Head, Link, usePage } from '@inertiajs/react';
import {
    ClipboardCheck,
    FileText,
    FlaskConical,
    Radar,
    ShieldAlert,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';
import type { Auth } from '@/types';

/**
 * The only page a signed-out visitor sees.
 *
 * Accounts here are made by an administrator, so there is nothing to sell and
 * nothing to sign up for. It says what the instance is for and where the way
 * in is, and names the five things it keeps, because that is the honest
 * description of the tool.
 */
const modules: { icon: LucideIcon; title: string; description: string }[] = [
    {
        icon: FileText,
        title: 'Decision records',
        description:
            'What was decided, what else was weighed, and which record replaced which.',
    },
    {
        icon: ClipboardCheck,
        title: 'Vetting log',
        description:
            'Proposals from the day they were raised to the day they got an answer.',
    },
    {
        icon: FlaskConical,
        title: 'Prototypes',
        description:
            'What a spike set out to prove, how it was tested, and how it landed.',
    },
    {
        icon: ShieldAlert,
        title: 'Security posture',
        description:
            'Findings, the triage call on each, and whether they were actually fixed.',
    },
    {
        icon: Radar,
        title: 'Tech radar',
        description:
            'Feeds worth reading, triaged down to the few things worth acting on.',
    },
];

export default function Welcome() {
    const { auth, name } = usePage<{ auth?: Auth; name: string }>().props;

    return (
        <>
            <Head title="Welcome" />

            <div className="flex min-h-dvh flex-col items-center justify-center bg-background p-6 text-foreground">
                <main className="w-full max-w-3xl space-y-10">
                    <header className="space-y-4 text-center">
                        <div className="mx-auto flex size-12 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                            <AppLogoIcon className="size-7" />
                        </div>

                        <div className="space-y-3">
                            <h1 className="text-3xl font-semibold tracking-tight">
                                {name}
                            </h1>
                            <p className="mx-auto max-w-xl text-sm leading-relaxed text-muted-foreground">
                                A watchtower for the technical leadership work
                                that leaves no trace anywhere else: the
                                decisions, the proposals behind them, the spikes
                                that tested them, and the findings raised along
                                the way.
                            </p>
                        </div>

                        <Button asChild size="lg">
                            <Link href={auth?.user ? dashboard() : login()}>
                                {auth?.user ? 'Open the dashboard' : 'Log in'}
                            </Link>
                        </Button>
                    </header>

                    <ul className="grid gap-3 sm:grid-cols-2">
                        {modules.map((module) => (
                            <li
                                key={module.title}
                                className="flex gap-3 rounded-xl border border-border bg-card p-4"
                            >
                                <module.icon className="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                                <div className="space-y-1">
                                    <h2 className="text-sm font-medium">
                                        {module.title}
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        {module.description}
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ul>

                    <p className="text-center text-xs text-muted-foreground">
                        Specula is Latin for a watchtower, and the root of
                        speculate. Accounts are created by an administrator.
                    </p>
                </main>
            </div>
        </>
    );
}
