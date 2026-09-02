<?php

namespace App\Http\Controllers;

use App\Concerns\RendersMarkdown;
use App\Http\Requests\Decisions\StoreDecisionRecordRequest;
use App\Http\Requests\Decisions\UpdateDecisionRecordRequest;
use App\Models\DecisionRecord;
use Inertia\Response;

class DecisionRecordController extends Controller
{
    use RendersMarkdown;

    /**
     * Display a listing of the resource.
     */
    public function index():Response
    {
        return inertia('decisions/index',[
            'records' => DecisionRecord::query()
            ->orderBy('project_prefix')
            ->orderBy('category')
            ->orderBy('sequence')
            ->get(['id','project_prefix','category','sequence','title','status','updated_at']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create():Response
    {
        return inertia('decisions/create',[
            'statuses' => $this->statusOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDecisionRecordRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(DecisionRecord $decisionRecord)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DecisionRecord $decisionRecord)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDecisionRecordRequest $request, DecisionRecord $decisionRecord)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DecisionRecord $decisionRecord)
    {
        //
    }
}
