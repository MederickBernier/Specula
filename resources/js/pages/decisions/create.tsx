import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { create, index, store } from '@/routes/decisions';
import type { SelectOption } from '@/types';
import DecisionForm from './decision-form';

export default function CreateDecision({
    statuses,
}: {
    statuses: SelectOption[];
}) {
    return (
        <>
            <Head title="New decision record" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="New decision record"
                    description="Markdown sections are rendered when reading the record back"
                />

                <DecisionForm
                    statuses={statuses}
                    submitLabel="Create record"
                    submit={(form) => form.submit(store())}
                />
            </div>
        </>
    );
}

CreateDecision.layout = {
    breadcrumbs: [
        { title: 'Decision records', href: index() },
        { title: 'New record', href: create() },
    ],
};
