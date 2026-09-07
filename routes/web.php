<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DecisionLinkController;
use App\Http\Controllers\DecisionRecordController;
use App\Http\Controllers\FeedSourceController;
use App\Http\Controllers\ItemLinkController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectNoteController;
use App\Http\Controllers\PrototypeController;
use App\Http\Controllers\RadarItemController;
use App\Http\Controllers\SavedSearchController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SecurityNoteController;
use App\Http\Controllers\VettingItemController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

// can-write is applied to the whole group rather than per route: every write in
// these modules is an unsafe HTTP method, so read-only accounts are blocked
// here once, including for modules added later.
Route::middleware(['auth', 'verified', 'can-write'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('search', SearchController::class)->name('search');

    Route::resource('projects', ProjectController::class);

    Route::get('projects/{project}/export', [ProjectController::class, 'export'])
        ->name('projects.export');

    Route::patch('projects/{project}/archive', [ProjectController::class, 'archive'])
        ->name('projects.archive');

    Route::post('projects/{project}/notes', [ProjectNoteController::class, 'store'])
        ->name('projects.notes.store');
    Route::put('project-notes/{projectNote}', [ProjectNoteController::class, 'update'])
        ->name('projects.notes.update');
    Route::delete('project-notes/{projectNote}', [ProjectNoteController::class, 'destroy'])
        ->name('projects.notes.destroy');

    Route::resource('decisions', DecisionRecordController::class)
        ->parameters(['decisions' => 'decisionRecord']);

    Route::get('decisions/{decisionRecord}/export', [DecisionRecordController::class, 'export'])
        ->name('decisions.export');

    Route::post('decisions/{decisionRecord}/links', [DecisionLinkController::class, 'store'])
        ->name('decisions.links.store');

    Route::delete('decision-links/{decisionLink}', [DecisionLinkController::class, 'destroy'])
        ->name('decisions.links.destroy');

    Route::resource('vetting', VettingItemController::class)
        ->parameters(['vetting' => 'vettingItem']);

    Route::resource('prototypes', PrototypeController::class);

    Route::get('radar', [RadarItemController::class, 'index'])->name('radar.index');
    Route::get('radar/{radarItem}', [RadarItemController::class, 'show'])
        ->whereNumber('radarItem')
        ->name('radar.show');
    Route::patch('radar/{radarItem}', [RadarItemController::class, 'triage'])->name('radar.triage');
    Route::post('radar/{radarItem}/promote', [RadarItemController::class, 'promote'])
        ->name('radar.promote');

    Route::get('radar-feeds', [FeedSourceController::class, 'index'])->name('radar.feeds.index');
    Route::post('radar-feeds', [FeedSourceController::class, 'store'])->name('radar.feeds.store');
    Route::put('radar-feeds/{feedSource}', [FeedSourceController::class, 'update'])
        ->name('radar.feeds.update');
    Route::delete('radar-feeds/{feedSource}', [FeedSourceController::class, 'destroy'])
        ->name('radar.feeds.destroy');
    Route::post('radar-feeds/{feedSource}/fetch', [FeedSourceController::class, 'fetch'])
        ->name('radar.feeds.fetch');

    Route::post('item-links', [ItemLinkController::class, 'store'])->name('item-links.store');
    Route::delete('item-links/{itemLink}', [ItemLinkController::class, 'destroy'])
        ->name('item-links.destroy');

    // Named security-notes rather than security: routes/settings.php already
    // owns the security.* names for the account security page.
    Route::resource('security-notes', SecurityNoteController::class)
        ->parameters(['security-notes' => 'securityNote']);
});

require __DIR__.'/settings.php';

// Saved radar filters. Outside the can-write group: a saved search is a private
// view of data the account can already read, so a read-only account keeps its
// own shortcuts without gaining the ability to change anything.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('radar/saved-searches', [SavedSearchController::class, 'store'])
        ->name('radar.saved-searches.store');
    Route::delete('radar/saved-searches/{savedSearch}', [SavedSearchController::class, 'destroy'])
        ->name('radar.saved-searches.destroy');
});
