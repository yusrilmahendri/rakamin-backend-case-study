<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Job;
use App\Models\Result;
use App\Models\Document;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;

class ProcessEvaluation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $jobId, $cvId, $reportId;

    public function __construct($jobId, $cvId, $reportId)
    {
        $this->jobId   = $jobId;
        $this->cvId    = $cvId;
        $this->reportId = $reportId;
    }

    public function handle()
    {
        $job = Job::find($this->jobId);

        if (!$job) {
            \Log::error("ProcessEvaluation error", [
                'jobId' => $this->jobId,
                'message' => 'Job not found'
            ]);
            return;
        }

        $job->status = 'processing';
        $job->save();

        try {
            \Log::info("ProcessEvaluation started", [
                'jobId' => $this->jobId,
                'cvId' => $this->cvId,
                'reportId' => $this->reportId,
            ]);

            // 1️⃣ Ambil dokumen
            $cvDoc     = Document::find($this->cvId);
            $reportDoc = Document::find($this->reportId);

            // 2️⃣ Validasi dokumen
            if (!$cvDoc || !$reportDoc) {
                \Log::error("ProcessEvaluation error", [
                    'jobId' => $this->jobId,
                    'message' => 'Document not found',
                    'cvId' => $this->cvId,
                    'reportId' => $this->reportId,
                ]);
                $job->status = 'failed';
                $job->save();
                return;
            }

            // 3️⃣ Parse PDF
            $parser     = new Parser();
            $cvText     = $parser->parseFile(storage_path("app/".$cvDoc->path))->getText();
            $reportText = $parser->parseFile(storage_path("app/".$reportDoc->path))->getText();

            // 4️⃣ Panggil API LLM (OpenAI)
            $cvEval = Http::withToken(env('OPENAI_API_KEY'))->post(
                'https://api.openai.com/v1/chat/completions',
                [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a CV evaluator.'],
                        ['role' => 'user', 'content' => "Evaluate CV: $cvText"]
                    ],
                    'temperature' => 0.2
                ]
            )->json();

            $projectEval = Http::withToken(env('OPENAI_API_KEY'))->post(
                'https://api.openai.com/v1/chat/completions',
                [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a project evaluator.'],
                        ['role' => 'user', 'content' => "Evaluate Report: $reportText"]
                    ],
                    'temperature' => 0.2
                ]
            )->json();

            // 5️⃣ Simulasi hasil
            $cv_match_rate    = rand(70, 95) / 100;
            $cv_feedback      = $cvEval['choices'][0]['message']['content'] ?? 'No feedback';
            $project_score    = rand(3, 5);
            $project_feedback = $projectEval['choices'][0]['message']['content'] ?? 'No feedback';
            $overall_summary  = "Candidate has strong backend skills with some AI exposure. Could improve resilience.";

            Result::create([
                'job_id'          => $this->jobId,
                'cv_match_rate'   => $cv_match_rate,
                'cv_feedback'     => $cv_feedback,
                'project_score'   => $project_score,
                'project_feedback'=> $project_feedback,
                'overall_summary' => $overall_summary
            ]);

            $job->status = 'completed';
            $job->save();

            \Log::info("ProcessEvaluation completed", [
                'jobId' => $this->jobId
            ]);

        } catch (\Exception $e) {
            \Log::error("ProcessEvaluation exception", [
                'jobId' => $this->jobId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $job->status = 'failed';
            $job->save();
        }
    }
}
