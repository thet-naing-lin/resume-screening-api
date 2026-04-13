<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_description_id',
        'filename',
        'file_path',
        'raw_text',
        'parsed_data',
        'uploaded_at',
    ];

    protected $casts = [
        'parsed_data' => 'array',   // stored as JSON, accessed as PHP array
        'uploaded_at' => 'datetime',
    ];

    // Belongs to a job description
    public function jobDescription()
    {
        return $this->belongsTo(JobDescription::class);
    }

    // One resume belongs to one candidate
    public function candidate()
    {
        return $this->hasOne(Candidate::class);
    }
}
