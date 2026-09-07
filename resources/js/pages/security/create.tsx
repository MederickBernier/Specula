import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { create, index, store } from '@/routes/security-notes';
import SecurityForm from './security-form';
import type { SecurityFormOptions } from './security-form';

export default function CreateSecurityNote(options: SecurityFormOptions) {
    return (
        <>
            <Head title="New security note" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="New security note"
                    description="A finding, the triage call on it, and where it goes next"
                />

                <SecurityForm
                    options={options}
                    submitLabel="Create note"
                    submit={(form) => form.submit(store())}
                />
            </div>
        </>
    );
}

CreateSecurityNote.layout = {
    breadcrumbs: [
        { title: 'Security posture', href: index() },
        { title: 'New note', href: create() },
    ],
};
