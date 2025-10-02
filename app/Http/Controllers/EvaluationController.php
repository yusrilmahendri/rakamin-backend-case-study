<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Job;
use App\Models\Result;
use App\Jobs\ProcessEvaluation;

class EvaluationController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'cv' => 'required|file|mimes:pdf',
            'report' => 'required|file|mimes:pdf',
        ]);

        $cvPath = $request->file('cv')->store('uploads');
        $reportPath = $request->file('report')->store('uploads');

        $cv = Document::create([
            'type' => 'cv',
            'filename' => $request->file('cv')->getClientOriginalName(),
            'path' => $cvPath
        ]);

        $report = Document::create([
            'type' => 'report',
            'filename' => $request->file('report')->getClientOriginalName(),
            'path' => $reportPath
        ]);

        return response()->json([
            'cv_id' => $cv->id,
            'report_id' => $report->id
        ]);
    }

    public function evaluate(Request $request)
    {
        $request->validate([
            'cv_id' => 'required|exists:documents,id',
            'report_id' => 'required|exists:documents,id',
        ]);

        $job = Job::create(['status' => 'queued']);

        ProcessEvaluation::dispatch($job->id, $request->cv_id, $request->report_id);

        return response()->json([
            'id' => $job->id,
            'status' => 'queued'
        ]);
    }

    public function status($jobId)
    {
        $job = Job::findOrFail($jobId);

        return response()->json([
            'id' => $job->id,
            'status' => $job->status
        ]);
    }

    public function result($id)
    {
        $job = Job::findOrFail($id);

        if ($job->status !== 'completed') {
            return response()->json([
                'id' => $job->id,
                'status' => $job->status
            ]);
        }

        $result = Result::where('job_id', $job->id)->first();
        return response()->json([
            'id' => $job->id,
            'status' => 'completed',
            'result' => $result
        ]);
    }
}
