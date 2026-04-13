<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobDescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'title',
        'required_skills',
        'qualifications',
        'experience_level',
    ];

    // Belongs to a user (HR who created it)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // One job description has many resumes
    public function resumes()
    {
        return $this->hasMany(Resume::class);
    }

    // One job description has many scores
    public function scores()
    {
        return $this->hasMany(Score::class);
    }

    // One job description has many interview questions
    public function interviewQuestions()
    {
        return $this->hasMany(InterviewQuestion::class);
    }
}
