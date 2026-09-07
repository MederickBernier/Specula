export type VettingItemSummary = {
    id: number;
    title: string;
    source_type: string;
    status: string;
    date_raised: string;
    date_resolved: string | null;
};

export type VettingItem = VettingItemSummary & {
    project_id: number | null;
    source_detail: string | null;
    proposal_description: string;
    assessment: string | null;
    rejection_reason: string | null;
    external_url: string | null;
};
