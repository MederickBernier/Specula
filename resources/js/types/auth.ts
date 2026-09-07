export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    is_admin?: boolean;
    is_read_only?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    /**
     * Whether this account may change data. Use it to decide what to render;
     * the server blocks the write either way.
     */
    canWrite: boolean;
    isAdmin: boolean;
};
