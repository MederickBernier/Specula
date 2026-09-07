export type ProjectSummary = {
    id: number;
    name: string;
    prefix: string;
    description: string | null;
    decision_records_count: number;
    vetting_items_count: number;
    prototypes_count: number;
    security_notes_count: number;
    notes_count: number;
};

export type Project = {
    id: number;
    name: string;
    prefix: string;
    description: string | null;
};

export type ProjectNote = {
    id: number;
    title: string;
    body: string;
    html: string | null;
    updated_at: string;
};

export type ProjectDecision = {
    id: number;
    project_prefix: string;
    category: string;
    sequence: number;
    document_id: string;
    title: string;
    status: string;
};

export type ProjectRow = {
    id: number;
    title: string;
    status: string;
    severity?: string;
    date_raised?: string;
    date_started?: string;
    date_flagged?: string;
    date_resolved?: string | null;
    date_completed?: string | null;
};
