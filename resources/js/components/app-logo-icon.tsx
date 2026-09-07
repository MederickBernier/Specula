import { Telescope } from 'lucide-react';
import type { ComponentProps } from 'react';

/**
 * The mark. Specula is Latin for a watchtower or lookout, and the spec notes
 * the double meaning with "speculate", so a glass pointed at the horizon says
 * both halves of it.
 *
 * Call sites pass fill-current for the old path-based mark; this one is drawn
 * with strokes, so it takes its colour from currentColor either way.
 */
export default function AppLogoIcon(props: ComponentProps<typeof Telescope>) {
    return <Telescope strokeWidth={1.75} {...props} />;
}
