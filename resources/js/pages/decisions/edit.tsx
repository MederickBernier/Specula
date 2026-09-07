import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { index, update } from '@/routes/decisions';
import type { SelectOption } from '@/types';
import DecisionForm from './decision-form';
import type { DecisionRecord } from './types';

export default function EditDecision({
    record,
    statuses,
    projects,
}: {
    record: DecisionRecord;
    statuses: SelectOption[];
    projects: SelectOption[];
}) {
    return (
        <>
            <Head title={`Edit ${record.document_id}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title={`Edit ${record.document_id}`}
                    description={record.title}
                />

                <DecisionForm
                    record={record}
                    statuses={statuses}
                    projects={projects}
                    submitLabel="Save changes"
                    submit={(form) => form.submit(update(record.id))}
                />
            </div>
        </>
    );
}

EditDecision.layout = {
    breadcrumbs: [
        { title: 'Decision records', href: index() },
        { title: 'Edit record', href: index() },
    ],
};
