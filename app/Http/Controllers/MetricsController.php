<?php

namespace App\Http\Controllers;

use App\Actions\MeasurePractice;
use Inertia\Inertia;
use Inertia\Response;

class MetricsController extends Controller
{
    /**
     * What the record-keeping says about the habit that produced it.
     */
    public function __invoke(MeasurePractice $measure): Response
    {
        return Inertia::render('metrics', ['metrics' => $measure()]);
    }
}
