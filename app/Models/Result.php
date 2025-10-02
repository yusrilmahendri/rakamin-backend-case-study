<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    use HasFactory;
     protected $fillable = [
        'job_id',
        'cv_match_rate',
        'cv_feedback',
        'project_score',
        'project_feedback',
        'overall_summary'
    ];
}
