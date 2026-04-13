<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'job_description_id',
        'question',
    ];

    // Belongs to a candidate
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    // Belongs to a job description
    public function jobDescription()
    {
        return $this->belongsTo(JobDescription::class);
    }
}
