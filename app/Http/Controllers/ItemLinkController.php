<?php

namespace App\Http\Controllers;

use App\Http\Requests\Links\StoreItemLinkRequest;
use App\Models\ItemLink;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ItemLinkController extends Controller
{
    /**
     * Link two records across modules.
     */
    public function store(StoreItemLinkRequest $request): RedirectResponse
    {
        ItemLink::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Link added.')]);

        return back();
    }

    /**
     * Remove a cross-module link.
     */
    public function destroy(ItemLink $itemLink): RedirectResponse
    {
        $itemLink->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Link removed.')]);

        return back();
    }
}
