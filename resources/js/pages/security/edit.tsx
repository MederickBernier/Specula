import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { index, update } from '@/routes/security-notes';
import SecurityForm from './security-form';
import type {SecurityFormOptions} from './security-form';
import type { SecurityNote } from './types';

export default function EditSecurityNote({
    note,
    ...options
}: { note: SecurityNote } & SecurityFormOptions) {
    return (
        <>
            <Head title={`Edit ${note.title}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading title="Edit security note" description={note.title} />

                <SecurityForm
                    note={note}
                    options={options}
                    submitLabel="Save changes"
                    submit={(form) => form.submit(update(note.id))}
                />
            </div>
        </>
    );
}

EditSecurityNote.layout = {
    breadcrumbs: [
        { title: 'Security posture', href: index() },
        { title: 'Edit note', href: index() },
    ],
};
