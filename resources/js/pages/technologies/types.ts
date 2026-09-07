export type Technology = {
    id: number;
    name: string;
    category: string;
    ring: string;
    status: string;
    vendor: string | null;
    homepage_url: string | null;
    notes: string | null;
    usages_count?: number;
};

export type UsageRecord = {
    id: number;
    label: string;
    url: string | null;
    version: string | null;
    role: string | null;
    notes: string | null;
};

export type UsageGroup = {
    type: string;
    label: string;
    records: UsageRecord[];
};

export type BreakdownProject = {
    id: number;
    name: string;
    prefix: string;
    archived: boolean;
    url: string;
};

export type BreakdownRow = {
    id: number;
    name: string;
    category: string;
    categoryLabel: string;
    ring: string;
    ringLabel: string;
    status: string;
    statusLabel: string;
    url: string;
    /** Project id to version; an empty string means used, version unrecorded. */
    projects: Record<number, string>;
    /** Carrier type to a count, for use that is not a project. */
    elsewhere: Record<string, number>;
    total: number;
};

export type BreakdownCategory = {
    category: string;
    label: string;
    technologies: BreakdownRow[];
};
