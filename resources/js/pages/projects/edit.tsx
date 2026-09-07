import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { index, update } from '@/routes/projects';
import ProjectForm from './project-form';
import type { Project } from './types';

export default function EditProject({ project }: { project: Project }) {
    return (
        <>
            <Head title={`Edit ${project.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Edit project"
                    description="Changing the prefix re-stamps every decision filed here"
                />

                <ProjectForm
                    project={project}
                    submitLabel="Save changes"
                    submit={(form) => form.submit(update(project.id))}
                />
            </div>
        </>
    );
}

EditProject.layout = {
    breadcrumbs: [
        { title: 'Projects', href: index() },
        { title: 'Edit project', href: index() },
    ],
};
