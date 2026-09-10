import { Telescope } from 'lucide-react';
import type { ComponentProps } from 'react';

/**
 * The mark. A glass is what makes a distant thing clear, which is the whole
 * claim in the name, so it points at the horizon rather than sitting on a
 * desk.
 *
 * Call sites pass fill-current for the old path-based mark; this one is drawn
 * with strokes, so it takes its colour from currentColor either way.
 */
export default function AppLogoIcon(props: ComponentProps<typeof Telescope>) {
    return <Telescope strokeWidth={1.75} {...props} />;
}
