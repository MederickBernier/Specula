<?php

use App\Http\Controllers\DecisionLinkController;
use App\Http\Controllers\DecisionRecordController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::resource('decisions', DecisionRecordController::class)
        ->parameters(['decisions' => 'decisionRecord']);

    Route::post('decisions/{decisionRecord}/links', [DecisionLinkController::class, 'store'])
        ->name('decisions.links.store');

    Route::delete('decision-links/{decisionLink}', [DecisionLinkController::class, 'destroy'])
        ->name('decisions.links.destroy');
});

require __DIR__.'/settings.php';
