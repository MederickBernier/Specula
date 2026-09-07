import * as React from 'react';

import { cn } from '@/lib/utils';
import type { SelectOption } from '@/types';

/**
 * A plain `<select>`. Used instead of the Radix select wherever the value has
 * to travel with a normal form submission.
 */
function NativeSelect({
    className,
    options,
    ...props
}: React.ComponentProps<'select'> & { options: SelectOption[] }) {
    return (
        <select
            data-slot="native-select"
            className={cn(
                // bg-card rather than transparent: on the slate page a control should read
                // as a surface you can act on. The option list itself is drawn by the
                // browser from color-scheme, which the theme sets once.
                'border-input focus-visible:border-ring focus-visible:ring-ring/50 bg-card h-9 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
                className,
            )}
            {...props}
        >
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
    );
}

export { NativeSelect };
