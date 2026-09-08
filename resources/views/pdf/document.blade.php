{{--
    The PDF template deliberately does not reuse the application stylesheet.
    dompdf supports neither flexbox nor grid, which the app is built on, so this
    is plain block and table CSS sized for A4.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 20mm 16mm; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5pt;
            line-height: 1.45;
            color: #1a1a1a;
        }

        h1, h2, h3, h4, h5, h6 { line-height: 1.25; }
        h1 { font-size: 19pt; margin: 0 0 4pt; }
        h6 { font-size: 10.5pt; margin: 10pt 0 3pt; }
        h2 { font-size: 14pt; margin: 18pt 0 4pt; border-bottom: 0.5pt solid #cccccc; padding-bottom: 3pt; }
        h3 { font-size: 12pt; margin: 14pt 0 3pt; }
        h4, h5 { font-size: 11pt; margin: 12pt 0 3pt; }

        p, ul, ol { margin: 0 0 8pt; }
        ul, ol { padding-left: 16pt; }
        li { margin-bottom: 2pt; }

        code {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 9pt;
            background: #f2f2f2;
            padding: 0 2pt;
        }

        pre {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 9pt;
            background: #f2f2f2;
            padding: 6pt;
            white-space: pre-wrap;
        }

        blockquote {
            margin: 0 0 8pt;
            padding-left: 8pt;
            border-left: 2pt solid #cccccc;
            color: #444444;
        }

        /* Fixed layout so a wide table wraps inside the page instead of running off it. */
        table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin: 0 0 10pt;
            font-size: 9.5pt;
        }

        th, td {
            border: 0.5pt solid #cccccc;
            padding: 3pt 5pt;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }

        th { background: #f2f2f2; }

        a { color: #1a1a1a; }

        .footer {
            margin-top: 18pt;
            padding-top: 6pt;
            border-top: 0.5pt solid #cccccc;
            font-size: 8pt;
            color: #666666;
        }
    </style>
</head>
<body>
{!! $html !!}

<p class="footer">{{ $title }} — exported from {{ config('app.name') }} on {{ $generatedAt }}</p>
</body>
</html>
