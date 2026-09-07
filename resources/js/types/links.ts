export type ItemLinkTarget = {
    type: string;
    label: string;
    records: { id: number; label: string }[];
};

export type ItemLink = {
    id: number;
    link_type: string;
    link_type_label: string;
    note: string | null;
    date_linked: string;
    other: {
        module: string;
        label: string;
        url: string;
    } | null;
};

/** The cross-module link props every module's show page receives. */
export type ItemLinkProps = {
    itemLinks: { outgoing: ItemLink[]; incoming: ItemLink[] };
    itemLinkTargets: ItemLinkTarget[];
    itemLinkTypes: { value: string; label: string }[];
    itemLinkSource: { type: string; id: number };
};
