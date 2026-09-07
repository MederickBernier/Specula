import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { destroy, edit, index } from '@/routes/decisions';
import links from '@/routes/decisions/links';
import type {
    DecisionLink,
    DecisionRecord,
    SelectOption,
} from './types';

type ShowProps = {
    record: DecisionRecord & {
        outgoing_links: DecisionLink[];
        incoming_links: DecisionLink[];
    };
    html: {
        proposal_context: string | null;
        recommendation: string | null;
        consequences: string | null;
        conditions_for_revisiting: string | null;
        options: Record<number, { description: string | null; pros: string | null; cons: string | null }>;
        links: Record<number, string | null>;
    };
    relationshipTypes: SelectOption[];
    linkTargets: DecisionRecord[];
};

function Markdown({ html }: { html: string | null }) {
    if (!html) {
        return <p className="text-muted-foreground text-sm">Not recorded.</p>;
    }

    // Safe: the server renders this with html_input stripped and unsafe links disallowed.
    return (
        <div
            className="prose prose-sm dark:prose-invert max-w-none [&_a]:underline [&_code]:font-mono [&_li]:my-1 [&_p]:my-2 [&_ul]:list-disc [&_ul]:pl-5"
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}

function Section({ title, html }: { title: string; html: string | null }) {
    return (
        <section className="space-y-2">
            <h2 className="text-lg font-medium">{title}</h2>
            <Markdown html={html} />
        </section>
    );
}

function LinkRows({
    heading,
    rows,
    direction,
    html,
    relationshipTypes,
}: {
    heading: string;
    rows: DecisionLink[];
    direction: 'outgoing' | 'incoming';
    html: Record<number, string | null>;
    relationshipTypes: SelectOption[];
}) {
    const labelFor = (value: string) =>
        relationshipTypes.find((type) => type.value === value)?.label ?? value;

    return (
        <div className="space-y-2">
            <h3 className="font-medium">{heading}</h3>

            {rows.length === 0 ? (
                <p className="text-muted-foreground text-sm">None.</p>
            ) : (
                <ul className="space-y-3">
                    {rows.map((link) => {
                        const other = direction === 'outgoing' ? link.target : link.source;

                        return (
                            <li
                                key={link.id}
                                className="border-sidebar-border/70 dark:border-sidebar-border flex items-start justify-between gap-4 rounded-lg border p-3"
                            >
                                <div className="space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge variant="outline">{labelFor(link.relationship_type)}</Badge>
                                        <span className="font-mono text-sm">{other?.document_id}</span>
                                        <span className="text-sm">{other?.title}</span>
                                    </div>
                                    {link.scope_note && (
                                        <p className="text-muted-foreground text-sm">Scope: {link.scope_note}</p>
                                    )}
                                    {link.role_note && (
                                        <p className="text-muted-foreground text-sm">Role: {link.role_note}</p>
                                    )}
                                    <Markdown html={html[link.id] ?? null} />
                                </div>

                                {direction === 'outgoing' && (
                                    <Form
                                        {...links.destroy.form(link.id)}
                                        options={{ preserveScroll: true }}
                                    >
                                        <Button
                                            type="submit"
                                            variant="ghost"
                                            size="icon"
                                            aria-label="Remove link"
                                        >
                                            <Trash2 />
                                        </Button>
                                    </Form>
                                )}
                            </li>
                        );
                    })}
                </ul>
            )}
        </div>
    );
}

export default function ShowDecision({ record, html, relationshipTypes, linkTargets }: ShowProps) {
    return (
        <>
            <Head title={record.document_id} />

            <div className="flex h-full flex-1 flex-col gap-8 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading title={`${record.document_id} — ${record.title}`} />

                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <Link href={edit(record.id)}>
                                <Pencil /> Edit
                            </Link>
                        </Button>

                        <Form {...destroy.form(record.id)}>
                            <Button type="submit" variant="destructive">
                                <Trash2 /> Delete
                            </Button>
                        </Form>
                    </div>
                </div>

                <dl className="grid gap-3 text-sm sm:grid-cols-4">
                    <div>
                        <dt className="text-muted-foreground">Status</dt>
                        <dd>{record.status}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Author</dt>
                        <dd>{record.author}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Deciders</dt>
                        <dd>{record.deciders ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Affects</dt>
                        <dd>{record.affects ?? '—'}</dd>
                    </div>
                </dl>

                <Section title="Context" html={html.proposal_context} />
                <Section title="Decision / recommendation" html={html.recommendation} />
                <Section title="Consequences" html={html.consequences} />
                <Section title="Conditions for revisiting" html={html.conditions_for_revisiting} />

                <section className="space-y-4">
                    <h2 className="text-lg font-medium">Options considered</h2>

                    {record.options.length === 0 ? (
                        <p className="text-muted-foreground text-sm">No options recorded.</p>
                    ) : (
                        record.options.map((option) => (
                            <div
                                key={option.id}
                                className="border-sidebar-border/70 dark:border-sidebar-border space-y-3 rounded-xl border p-4"
                            >
                                <div className="flex items-center gap-2">
                                    <h3 className="font-medium">{option.name}</h3>
                                    {option.was_chosen && <Badge>Chosen</Badge>}
                                </div>

                                <Markdown html={html.options[option.id!]?.description ?? null} />

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <h4 className="text-muted-foreground text-sm">Pros</h4>
                                        <Markdown html={html.options[option.id!]?.pros ?? null} />
                                    </div>
                                    <div>
                                        <h4 className="text-muted-foreground text-sm">Cons</h4>
                                        <Markdown html={html.options[option.id!]?.cons ?? null} />
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </section>

                <section className="space-y-6">
                    <h2 className="text-lg font-medium">Cross-references</h2>

                    <LinkRows
                        heading="This record points at"
                        rows={record.outgoing_links}
                        direction="outgoing"
                        html={html.links}
                        relationshipTypes={relationshipTypes}
                    />

                    <LinkRows
                        heading="Pointed at by"
                        rows={record.incoming_links}
                        direction="incoming"
                        html={html.links}
                        relationshipTypes={relationshipTypes}
                    />

                    {linkTargets.length > 0 && (
                        <Form
                            {...links.store.form(record.id)}
                            options={{ preserveScroll: true }}
                            resetOnSuccess
                            className="border-sidebar-border/70 dark:border-sidebar-border space-y-4 rounded-xl border p-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <h3 className="font-medium">Add a link</h3>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="target_id">Target record</Label>
                                            <select
                                                id="target_id"
                                                name="target_id"
                                                required
                                                className="border-input focus-visible:border-ring focus-visible:ring-ring/50 h-9 w-full rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                            >
                                                {linkTargets.map((target) => (
                                                    <option key={target.id} value={target.id}>
                                                        {target.document_id} — {target.title}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError message={errors.target_id} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="relationship_type">Relationship</Label>
                                            <select
                                                id="relationship_type"
                                                name="relationship_type"
                                                required
                                                className="border-input focus-visible:border-ring focus-visible:ring-ring/50 h-9 w-full rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                            >
                                                {relationshipTypes.map((type) => (
                                                    <option key={type.value} value={type.value}>
                                                        {type.label}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError message={errors.relationship_type} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="scope_note">Scope note</Label>
                                            <Input id="scope_note" name="scope_note" />
                                            <InputError message={errors.scope_note} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="role_note">Role note</Label>
                                            <Input id="role_note" name="role_note" />
                                            <InputError message={errors.role_note} />
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="impact_summary">
                                            Impact summary <span className="text-muted-foreground">(markdown)</span>
                                        </Label>
                                        <Textarea id="impact_summary" name="impact_summary" rows={4} />
                                        <InputError message={errors.impact_summary} />
                                    </div>

                                    <Button type="submit" disabled={processing}>
                                        Add link
                                    </Button>
                                </>
                            )}
                        </Form>
                    )}
                </section>
            </div>
        </>
    );
}

ShowDecision.layout = {
    breadcrumbs: [
        { title: 'Decision records', href: index() },
        { title: 'Record', href: index() },
    ],
};
