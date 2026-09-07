<?php

namespace App\Http\Controllers;

use App\Concerns\OffersProjects;
use App\Concerns\PresentsItemLinks;
use App\Concerns\RendersMarkdown;
use App\Enums\VettingSourceType;
use App\Enums\VettingStatus;
use App\Http\Requests\Vetting\StoreVettingItemRequest;
use App\Http\Requests\Vetting\UpdateVettingItemRequest;
use App\Models\VettingItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VettingItemController extends Controller
{
    use OffersProjects;
    use PresentsItemLinks;
    use RendersMarkdown;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $project = $this->projectFilter($request);

        return Inertia::render('vetting/index', [
            'items' => VettingItem::query()
                ->tap(fn ($query) => $this->scopeToProject($query, $project))
                ->orderByDesc('date_raised')
                ->orderByDesc('id')
                ->get(['id', 'title', 'source_type', 'status', 'date_raised', 'date_resolved']),
            'projectFilters' => $this->projectFilterOptions(),
            'projectFilter' => $project ?? '',
            'statuses' => VettingStatus::options(),
            'sourceTypes' => VettingSourceType::options(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('vetting/create', $this->formOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVettingItemRequest $request): RedirectResponse
    {
        $item = VettingItem::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vetting item created.')]);

        return to_route('vetting.show', $item);
    }

    /**
     * Display the specified resource.
     */
    public function show(VettingItem $vettingItem): Response
    {
        return Inertia::render('vetting/show', [
            'item' => $vettingItem,
            ...$this->itemLinkProps($vettingItem),
            'html' => [
                'proposal_description' => $this->renderMarkdown($vettingItem->proposal_description),
                'assessment' => $this->renderMarkdown($vettingItem->assessment),
                'rejection_reason' => $this->renderMarkdown($vettingItem->rejection_reason),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VettingItem $vettingItem): Response
    {
        return Inertia::render('vetting/edit', [
            'item' => $vettingItem,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVettingItemRequest $request, VettingItem $vettingItem): RedirectResponse
    {
        $vettingItem->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vetting item updated.')]);

        return to_route('vetting.show', $vettingItem);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VettingItem $vettingItem): RedirectResponse
    {
        $vettingItem->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vetting item deleted.')]);

        return to_route('vetting.index');
    }

    /**
     * The select options both the create and edit forms need.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'projects' => $this->projectOptions(),
            'statuses' => VettingStatus::options(),
            'sourceTypes' => VettingSourceType::options(),
        ];
    }
}
