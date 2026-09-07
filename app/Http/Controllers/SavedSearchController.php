<?php

namespace App\Http\Controllers;

use App\Http\Requests\Radar\StoreSavedSearchRequest;
use App\Models\SavedSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Named radar filters, one set per person.
 *
 * These sit outside the write guard on purpose: a saved search is a private
 * view of data the account can already read, and cannot change anything anyone
 * else sees. A read-only account gets to keep its own shortcuts.
 */
class SavedSearchController extends Controller
{
    /**
     * Save the current radar filters under a name.
     */
    public function store(StoreSavedSearchRequest $request): RedirectResponse
    {
        $filters = $request->filters();

        if ($filters === []) {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => __('Narrow the queue first, then save it.'),
            ]);

            return back();
        }

        $request->user()?->savedSearches()->create([
            'name' => $request->validated('name'),
            'filters' => $filters,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Search saved.')]);

        return back();
    }

    /**
     * Forget a saved search.
     */
    public function destroy(Request $request, SavedSearch $savedSearch): RedirectResponse
    {
        // Saved searches are private, so one account never acts on another's.
        abort_unless($savedSearch->user_id === $request->user()?->id, 403);

        $savedSearch->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Search forgotten.')]);

        return back();
    }
}
