import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { create, index, store } from '@/routes/prototypes';
import type { SelectOption } from '@/types';
import PrototypeForm from './prototype-form';

export default function CreatePrototype({
    statuses,
    confidenceLevels,
    projects,
}: {
    statuses: SelectOption[];
    confidenceLevels: SelectOption[];
    projects: SelectOption[];
}) {
    return (
        <>
            <Head title="New prototype" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="New prototype"
                    description="What you are testing, and what would count as an answer"
                />

                <PrototypeForm
                    statuses={statuses}
                    confidenceLevels={confidenceLevels}
                    projects={projects}
                    submitLabel="Create prototype"
                    submit={(form) => form.submit(store())}
                />
            </div>
        </>
    );
}

CreatePrototype.layout = {
    breadcrumbs: [
        { title: 'Prototypes', href: index() },
        { title: 'New prototype', href: create() },
    ],
};
