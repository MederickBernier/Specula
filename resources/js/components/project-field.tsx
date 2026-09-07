import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import type { SelectOption } from '@/types';

/**
 * Files a record under a project. Optional everywhere: plenty of this work is
 * not attached to one.
 */
export default function ProjectField({
    projects,
    value,
    onChange,
    error,
    hint,
}: {
    projects: SelectOption[];
    value: string;
    onChange: (value: string) => void;
    error?: string;
    hint?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor="project_id">Project</Label>
            <NativeSelect
                id="project_id"
                options={[{ value: '', label: 'No project' }, ...projects]}
                value={value}
                onChange={(event) => onChange(event.target.value)}
            />
            {hint && <p className="text-sm text-muted-foreground">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}
