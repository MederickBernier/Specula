import { useHttp } from '@inertiajs/react';
import { Eye, Pencil } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Markdown } from '@/components/markdown';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { preview } from '@/routes/markdown';

/**
 * A markdown field with a preview of what it will actually look like.
 *
 * The preview is rendered by the server through the same converter the show
 * page and the PDF use, so the three cannot disagree. It is fetched when
 * Preview is pressed rather than as you type: previewing is a deliberate act,
 * so there is no debounce to tune and no request per keystroke.
 */
export default function MarkdownField({
    id,
    name,
    label,
    value,
    onChange,
    error,
    rows = 8,
    required = false,
    hint,
    placeholder,
}: {
    id: string;
    /** Set for a plain <Form> field, which serialises by name rather than state. */
    name?: string;
    label: string;
    value?: string;
    onChange?: (value: string) => void;
    error?: string;
    rows?: number;
    required?: boolean;
    hint?: string;
    placeholder?: string;
}) {
    const http = useHttp<{ text: string }, { html: string | null }>(preview(), {
        text: '',
    });
    // Uncontrolled use still needs the text in hand to preview it, so the
    // component keeps its own copy when the parent is not holding one.
    const [ownValue, setOwnValue] = useState('');
    const text = value ?? ownValue;
    const [showing, setShowing] = useState<'write' | 'preview'>('write');
    const [html, setHtml] = useState<string | null>(null);
    const [previewed, setPreviewed] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    const showPreview = async () => {
        setShowing('preview');

        if (previewed === text) {
            return;
        }

        setLoading(true);

        try {
            http.transform(() => ({ text }));

            const response = await http.submit();

            setHtml(response?.html ?? null);
            setPreviewed(text);
        } catch {
            // Falling back to the source is more useful than an error banner:
            // the text is still there to keep writing.
            setShowing('write');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="grid gap-2">
            <div className="flex items-center justify-between gap-3">
                <Label htmlFor={id}>
                    {label}{' '}
                    <span className="text-muted-foreground">(markdown)</span>
                </Label>

                <div className="flex items-center gap-1">
                    <Button
                        type="button"
                        size="sm"
                        variant={showing === 'write' ? 'secondary' : 'ghost'}
                        onClick={() => setShowing('write')}
                    >
                        <Pencil /> Write
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant={showing === 'preview' ? 'secondary' : 'ghost'}
                        onClick={showPreview}
                    >
                        <Eye /> Preview
                    </Button>
                </div>
            </div>

            {showing === 'write' ? (
                <Textarea
                    id={id}
                    name={name}
                    value={text}
                    onChange={(event) => {
                        setOwnValue(event.target.value);
                        onChange?.(event.target.value);
                    }}
                    rows={rows}
                    required={required}
                    placeholder={placeholder}
                />
            ) : (
                <div
                    className="min-h-24 rounded-md border border-input bg-card px-3 py-2"
                    style={{ minHeight: `${rows * 1.6}rem` }}
                >
                    {loading ? (
                        <p className="text-sm text-muted-foreground">
                            Rendering…
                        </p>
                    ) : (
                        <Markdown html={html} />
                    )}
                </div>
            )}

            {hint && <p className="text-sm text-muted-foreground">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}
