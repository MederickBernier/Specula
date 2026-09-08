import { Form, useForm } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Markdown } from '@/components/markdown';
import MarkdownField from '@/components/markdown-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePermissions } from '@/hooks/use-permissions';
import { destroy, store, update } from '@/routes/projects/notes';
import type { ProjectNote } from './types';

function NoteForm({
    projectId,
    note,
    onDone,
}: {
    projectId: number;
    note?: ProjectNote;
    onDone?: () => void;
}) {
    const form = useForm({
        title: note?.title ?? '',
        body: note?.body ?? '',
    });

    const { data, setData, processing, errors, reset } = form;

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.submit(note ? update(note.id) : store(projectId), {
                    preserveScroll: true,
                    onSuccess: () => {
                        if (!note) {
                            reset();
                        }

                        onDone?.();
                    },
                });
            }}
            className="space-y-3"
        >
            <div className="grid gap-2">
                <Label htmlFor={`note-title-${note?.id ?? 'new'}`}>Title</Label>
                <Input
                    id={`note-title-${note?.id ?? 'new'}`}
                    value={data.title}
                    onChange={(event) => setData('title', event.target.value)}
                    required
                />
                <InputError message={errors.title} />
            </div>

            <MarkdownField
                id={`note-body-${note?.id ?? 'new'}`}
                label="Note"
                value={data.body}
                onChange={(next) => setData('body', next)}
                error={errors.body}
                rows={5}
                required
            />

            <div className="flex items-center gap-2">
                <Button type="submit" size="sm" disabled={processing}>
                    {note ? 'Save note' : 'Add note'}
                </Button>
                {note && onDone && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={onDone}
                    >
                        Cancel
                    </Button>
                )}
            </div>
        </form>
    );
}

/**
 * The things that are not a decision, a proposal, a spike or a finding, but are
 * still worth writing down against the project.
 */
export default function ProjectNotes({
    projectId,
    notes,
}: {
    projectId: number;
    notes: ProjectNote[];
}) {
    const { canWrite } = usePermissions();
    const [editing, setEditing] = useState<number | null>(null);

    return (
        <section className="space-y-4">
            <h2 className="text-lg font-medium">Notes</h2>

            {canWrite && (
                <div className="rounded-xl border border-sidebar-border/70 p-4">
                    <NoteForm projectId={projectId} />
                </div>
            )}

            {notes.length === 0 ? (
                <p className="text-sm text-muted-foreground">No notes yet.</p>
            ) : (
                <ul className="space-y-3">
                    {notes.map((note) => (
                        <li
                            key={note.id}
                            className="space-y-3 rounded-xl border border-sidebar-border/70 p-4"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <h3 className="font-medium">
                                        {note.title}
                                    </h3>
                                    <p className="text-xs text-muted-foreground">
                                        {new Date(
                                            note.updated_at,
                                        ).toLocaleString()}
                                    </p>
                                </div>

                                {canWrite && (
                                    <div className="flex items-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label={`Edit ${note.title}`}
                                            onClick={() =>
                                                setEditing(
                                                    editing === note.id
                                                        ? null
                                                        : note.id,
                                                )
                                            }
                                        >
                                            <Pencil />
                                        </Button>

                                        <Form
                                            {...destroy.form(note.id)}
                                            options={{ preserveScroll: true }}
                                        >
                                            <Button
                                                type="submit"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Remove ${note.title}`}
                                            >
                                                <Trash2 />
                                            </Button>
                                        </Form>
                                    </div>
                                )}
                            </div>

                            {canWrite && editing === note.id ? (
                                <NoteForm
                                    projectId={projectId}
                                    note={note}
                                    onDone={() => setEditing(null)}
                                />
                            ) : (
                                <Markdown html={note.html} />
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
