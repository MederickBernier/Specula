import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { create, index, store } from '@/routes/vetting';
import type { SelectOption } from '@/types';
import VettingForm from './vetting-form';

export default function CreateVettingItem({
    statuses,
    sourceTypes,
}: {
    statuses: SelectOption[];
    sourceTypes: SelectOption[];
}) {
    return (
        <>
            <Head title="New vetting item" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="New vetting item"
                    description="A proposal to assess before it becomes a commitment"
                />

                <VettingForm
                    statuses={statuses}
                    sourceTypes={sourceTypes}
                    submitLabel="Create item"
                    submit={(form) => form.submit(store())}
                />
            </div>
        </>
    );
}

CreateVettingItem.layout = {
    breadcrumbs: [
        { title: 'Vetting log', href: index() },
        { title: 'New item', href: create() },
    ],
};
