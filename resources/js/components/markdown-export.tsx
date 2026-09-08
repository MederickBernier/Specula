import { Check, Copy, Download, FileText } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { useClipboard } from '@/hooks/use-clipboard';

/**
 * Gets a record out of the app: copied for pasting into a pull request or a
 * wiki, or downloaded as a file.
 *
 * `markdown` is only passed where the page already has the document; otherwise
 * the download link is the whole control.
 */
export default function MarkdownExport({
    downloadUrl,
    markdown,
    label = 'Download',
}: {
    /** The markdown route. The PDF is the same URL asking for a format. */
    downloadUrl: string;
    markdown?: string;
    label?: string;
}) {
    const [, copy] = useClipboard();
    const [copied, setCopied] = useState(false);

    return (
        <div className="flex items-center gap-2">
            {markdown && (
                <Button
                    variant="outline"
                    onClick={async () => {
                        if (await copy(markdown)) {
                            setCopied(true);
                            window.setTimeout(() => setCopied(false), 2000);
                        }
                    }}
                >
                    {copied ? <Check /> : <Copy />}
                    {copied ? 'Copied' : 'Copy markdown'}
                </Button>
            )}

            <Button variant="outline" asChild>
                <a href={downloadUrl} download>
                    <Download /> {label} .md
                </a>
            </Button>

            <Button variant="outline" asChild>
                <a href={`${downloadUrl}?format=pdf`} download>
                    <FileText /> {label} .pdf
                </a>
            </Button>
        </div>
    );
}
