export type DecisionOption = {
    id?: number;
    name: string;
    description: string | null;
    pros: string | null;
    cons: string | null;
    was_chosen: boolean;
};

export type DecisionRecordSummary = {
    id: number;
    document_id: string;
    project_prefix: string;
    category: string;
    sequence: number;
    title: string;
    status?: string;
    updated_at?: string;
};

export type DecisionRecord = DecisionRecordSummary & {
    status: string;
    author: string;
    deciders: string | null;
    affects: string | null;
    proposal_context: string;
    recommendation: string;
    consequences: string | null;
    conditions_for_revisiting: string | null;
    options: DecisionOption[];
};

export type DecisionLink = {
    id: number;
    source_id: number;
    target_id: number;
    relationship_type: string;
    scope_note: string | null;
    role_note: string | null;
    impact_summary: string | null;
    source?: DecisionRecordSummary;
    target?: DecisionRecordSummary;
};
