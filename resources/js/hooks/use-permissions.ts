import { usePage } from '@inertiajs/react';
import type { Auth } from '@/types';

/**
 * What the signed-in account is allowed to do.
 *
 * This decides what the UI offers, not what the server permits: every write is
 * blocked server-side for read-only accounts regardless of what is rendered.
 */
export function usePermissions(): { canWrite: boolean; isAdmin: boolean } {
    const auth = usePage<{ auth?: Auth }>().props.auth;

    return {
        canWrite: auth?.canWrite ?? false,
        isAdmin: auth?.isAdmin ?? false,
    };
}
