/**
 * Renders markdown that the server already converted to HTML.
 *
 * Safe to inject: the backend renders it through the RendersMarkdown concern,
 * which strips raw HTML input and disallows unsafe links.
 */
export function Markdown({ html }: { html: string | null }) {
    if (!html) {
        return <p className="text-sm text-muted-foreground">Not recorded.</p>;
    }

    return (
        <div
            className="prose prose-sm max-w-none [&_a]:underline [&_code]:font-mono [&_li]:my-1 [&_p]:my-2 [&_ul]:list-disc [&_ul]:pl-5"
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}

export function MarkdownSection({
    title,
    html,
}: {
    title: string;
    html: string | null;
}) {
    return (
        <section className="space-y-2">
            <h2 className="text-lg font-medium">{title}</h2>
            <Markdown html={html} />
        </section>
    );
}
