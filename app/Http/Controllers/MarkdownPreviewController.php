<?php

namespace App\Http\Controllers;

use App\Actions\RenderMarkdownToHtml;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Renders what someone is typing, without saving it.
 *
 * A POST that writes nothing, so it sits outside the write guard: a read-only
 * account has no form to preview from, but nothing here would let it change
 * anything if it did.
 */
class MarkdownPreviewController extends Controller
{
    public function __invoke(Request $request, RenderMarkdownToHtml $render): JsonResponse
    {
        $validated = $request->validate([
            // Capped so the endpoint cannot be used to post megabytes at the
            // converter. Longer than anything anyone types into a field.
            'text' => ['nullable', 'string', 'max:50000'],
        ]);

        return response()->json([
            'html' => $render($validated['text'] ?? null),
        ]);
    }
}
