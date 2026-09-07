export type PrototypeSummary = {
    id: number;
    title: string;
    status: string;
    confidence_level: string | null;
    is_reusable: boolean | null;
    date_started: string;
    date_completed: string | null;
};

export type Prototype = PrototypeSummary & {
    project_id: number | null;
    hypothesis: string;
    test_approach: string | null;
    result: string | null;
    abandoned_reason: string | null;
    reusability_note: string | null;
    repo_reference: string | null;
};
