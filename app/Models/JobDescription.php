<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobDescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'required_skills',
        'experience_level',
        'employment_type',
        'location',
        'status',
    ];

    // Auto cast required_skills JSON to array
    protected $casts = [
        'required_skills' => 'array',
    ];

    // Relationship — belongs to the user who created it
    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
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
