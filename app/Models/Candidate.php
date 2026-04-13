<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'resume_id',
        'name',
        'email',
        'phone',
        'extracted_skills',
        'experience_years',
    ];

    protected $casts = [
        'extracted_skills' => 'array',  // stored as JSON
    ];

    // Belongs to a resume
    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }

    // One candidate has many scores
    public function scores()
    {
        return $this->hasMany(Score::class);
    }

    // One candidate has many interview questions
    public function interviewQuestions()
    {
        return $this->hasMany(InterviewQuestion::class);
    }
}
