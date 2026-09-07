import { router } from '@inertiajs/react';
import { NativeSelect } from '@/components/ui/native-select';
import type { SelectOption } from '@/types';

/**
 * Narrows a module index to one project, or to the work filed under none.
 *
 * The value goes on the query string so the filter survives a refresh and can
 * be linked to.
 */
export default function ProjectFilter({
    url,
    options,
    value,
}: {
    url: string;
    options: SelectOption[];
    value: string;
}) {
    if (options.length <= 2) {
        return null;
    }

    return (
        <NativeSelect
            aria-label="Filter by project"
            className="w-64"
            options={options}
            value={value}
            onChange={(event) =>
                router.get(
                    url,
                    event.target.value ? { project: event.target.value } : {},
                    { preserveScroll: true },
                )
            }
        />
    );
}
