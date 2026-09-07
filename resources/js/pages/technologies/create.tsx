import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { create, index, store } from '@/routes/technologies';
import type { SelectOption } from '@/types';
import TechnologyForm from './technology-form';

export default function CreateTechnology(options: {
    categories: SelectOption[];
    rings: SelectOption[];
    statuses: SelectOption[];
}) {
    return (
        <>
            <Head title="Add technology" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Add technology"
                    description="Something the estate is built from"
                />

                <TechnologyForm
                    {...options}
                    submitLabel="Add technology"
                    submit={(form) => form.submit(store())}
                />
            </div>
        </>
    );
}

CreateTechnology.layout = {
    breadcrumbs: [
        { title: 'Technologies', href: index() },
        { title: 'Add technology', href: create() },
    ],
};
