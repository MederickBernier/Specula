<?php

namespace App\Http\Controllers;

use App\Actions\SearchEverything;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    /**
     * Search everything written down, in one place.
     */
    public function __invoke(Request $request, SearchEverything $search): Response
    {
        $term = trim((string) $request->query('q'));

        $groups = $term === '' ? [] : $search($term);

        return Inertia::render('search', [
            'term' => $term,
            'groups' => $groups,
            'total' => array_sum(array_column($groups, 'total')),
        ]);
    }
}
