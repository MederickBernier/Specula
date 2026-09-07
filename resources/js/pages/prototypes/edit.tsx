import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { index, update } from '@/routes/prototypes';
import type { SelectOption } from '@/types';
import PrototypeForm from './prototype-form';
import type { Prototype } from './types';

export default function EditPrototype({
    prototype,
    statuses,
    confidenceLevels,
    projects,
}: {
    prototype: Prototype;
    statuses: SelectOption[];
    confidenceLevels: SelectOption[];
    projects: SelectOption[];
}) {
    return (
        <>
            <Head title={`Edit ${prototype.title}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading title="Edit prototype" description={prototype.title} />

                <PrototypeForm
                    prototype={prototype}
                    statuses={statuses}
                    confidenceLevels={confidenceLevels}
                    projects={projects}
                    submitLabel="Save changes"
                    submit={(form) => form.submit(update(prototype.id))}
                />
            </div>
        </>
    );
}

EditPrototype.layout = {
    breadcrumbs: [
        { title: 'Prototypes', href: index() },
        { title: 'Edit prototype', href: index() },
    ],
};
