<?php

namespace App\Http\Controllers;

use App\Actions\RenderDecisionRecordMarkdown;
use App\Actions\RenderEverythingMarkdown;
use App\Actions\RenderMarkdownPdf;
use App\Actions\RenderProjectMarkdown;
use App\Actions\RenderPrototypeMarkdown;
use App\Actions\RenderSecurityNoteMarkdown;
use App\Actions\RenderTechnologyMarkdown;
use App\Actions\RenderVettingItemMarkdown;
use App\Contracts\ExportsToMarkdown;
use App\Models\DecisionRecord;
use App\Models\Project;
use App\Models\Prototype;
use App\Models\SecurityNote;
use App\Models\Technology;
use App\Models\VettingItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Getting records out of the app, as markdown or as a PDF.
 *
 * One action per type rather than a type parameter, so route model binding does
 * the lookup and a bad id is a 404 rather than something this has to check.
 * Every route is a GET, so a read-only account can take a copy of anything it
 * is allowed to read.
 */
class ExportController extends Controller
{
    public function decision(Request $request, DecisionRecord $decisionRecord, RenderDecisionRecordMarkdown $render): Response
    {
        return $this->document($request, $decisionRecord, $render);
    }

    public function project(Request $request, Project $project, RenderProjectMarkdown $render): Response
    {
        return $this->document($request, $project, $render);
    }

    public function vettingItem(Request $request, VettingItem $vettingItem, RenderVettingItemMarkdown $render): Response
    {
        return $this->document($request, $vettingItem, $render);
    }

    public function prototype(Request $request, Prototype $prototype, RenderPrototypeMarkdown $render): Response
    {
        return $this->document($request, $prototype, $render);
    }

    public function securityNote(Request $request, SecurityNote $securityNote, RenderSecurityNoteMarkdown $render): Response
    {
        return $this->document($request, $securityNote, $render);
    }

    public function technology(Request $request, Technology $technology, RenderTechnologyMarkdown $render): Response
    {
        return $this->document($request, $technology, $render);
    }

    /**
     * The whole archive as one document.
     */
    public function everything(Request $request, RenderEverythingMarkdown $render): Response
    {
        return $this->respond(
            $request,
            $render(),
            $render->basename(),
            config('app.name').' archive',
        );
    }

    private function document(Request $request, Model $record, ExportsToMarkdown $render): Response
    {
        return $this->respond(
            $request,
            $render($record),
            $render->basename($record),
            $this->title($record),
        );
    }

    /**
     * Markdown by default; a PDF only when it is asked for by name, so an
     * unexpected format never silently produces the wrong file.
     */
    private function respond(Request $request, string $markdown, string $basename, string $title): Response
    {
        if ($request->query('format') === 'pdf') {
            return app(RenderMarkdownPdf::class)($markdown, $title)
                ->download($basename.'.pdf');
        }

        return response($markdown, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$basename.'.md"',
        ]);
    }

    /**
     * What the PDF calls itself, in its title and its footer.
     */
    private function title(Model $record): string
    {
        return match (true) {
            $record instanceof DecisionRecord => $record->document_id.' — '.$record->title,
            $record instanceof Project => $record->prefix.' — '.$record->name,
            $record instanceof Technology => $record->name,
            default => (string) $record->getAttribute('title'),
        };
    }
}
