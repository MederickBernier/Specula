import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { index, update } from '@/routes/decisions';
import DecisionForm from './decision-form';
import type { DecisionRecord, SelectOption } from './types';

export default function EditDecision({
    record,
    statuses,
}: {
    record: DecisionRecord;
    statuses: SelectOption[];
}) {
    return (
        <>
            <Head title={`Edit ${record.document_id}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading title={`Edit ${record.document_id}`} description={record.title} />

                <DecisionForm
                    record={record}
                    statuses={statuses}
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
