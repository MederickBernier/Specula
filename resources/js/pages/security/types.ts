export type SecurityNoteSummary = {
    id: number;
    title: string;
    source: string;
    category: string | null;
    severity: string;
    is_issue: boolean;
    routed_to: string;
    status: string;
    date_flagged: string;
    date_resolved: string | null;
};

export type SecurityNote = SecurityNoteSummary & {
    finding: string;
    non_issue_reason: string | null;
    deferral_reason: string | null;
    external_url: string | null;
};
