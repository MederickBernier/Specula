import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { create, index, store } from '@/routes/projects';
import ProjectForm from './project-form';

export default function CreateProject() {
    return (
        <>
            <Head title="New project" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="New project"
                    description="A prefix like VNG, and everything else hangs off it"
                />

                <ProjectForm
                    submitLabel="Create project"
                    submit={(form) => form.submit(store())}
                />
            </div>
        </>
    );
}

CreateProject.layout = {
    breadcrumbs: [
        { title: 'Projects', href: index() },
        { title: 'New project', href: create() },
    ],
};
