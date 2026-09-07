export type RadarItem = {
    id: number;
    title: string;
    url: string;
    summary: string | null;
    published_at: string | null;
    fetched_at: string;
    triage_status: string;
    triaged_at: string | null;
    relevance_note: string | null;
    is_hidden: boolean;
    feed_source: { id: number; name: string } | null;
    /** Whether this item has already produced a vetting item. */
    promoted?: boolean;
};

export type FeedSource = {
    id: number;
    name: string;
    url: string;
    feed_type: string;
    is_active: boolean;
    last_fetched_at: string | null;
    last_error: string | null;
    radar_items_count: number;
};

export type SavedSearch = {
    id: number;
    name: string;
    filters: { q?: string; feed?: string; status?: string };
};
