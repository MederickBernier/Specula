import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { index, update } from '@/routes/vetting';
import type { SelectOption } from '@/types';
import type { VettingItem } from './types';
import VettingForm from './vetting-form';

export default function EditVettingItem({
    item,
    statuses,
    sourceTypes,
    projects,
}: {
    item: VettingItem;
    statuses: SelectOption[];
    sourceTypes: SelectOption[];
    projects: SelectOption[];
}) {
    return (
        <>
            <Head title={`Edit ${item.title}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading title="Edit vetting item" description={item.title} />

                <VettingForm
                    item={item}
                    statuses={statuses}
                    sourceTypes={sourceTypes}
                    projects={projects}
                    submitLabel="Save changes"
                    submit={(form) => form.submit(update(item.id))}
                />
            </div>
        </>
    );
}

EditVettingItem.layout = {
    breadcrumbs: [
        { title: 'Vetting log', href: index() },
        { title: 'Edit item', href: index() },
    ],
};
