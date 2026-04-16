<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    use HasFactory;

    protected $fillable = [
        'resume_id',
        'job_description_id',
        'tfidf_score',
        'semantic_score',
        'final_score',
        'status',
        'scored_at',
    ];

    protected $casts = [
        'scored_at'      => 'datetime',
        'tfidf_score'    => 'float',
        'semantic_score' => 'float',
        'final_score'    => 'float',
    ];

    // Belongs to a job description
    public function jobDescription()
    {
        return $this->belongsTo(JobDescription::class);
    }

    // Belongs to a resume
    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
