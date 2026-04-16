<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_description_id',
        'uploaded_by',
        'candidate_id',
        'original_filename',
        'stored_filename',
        'file_type',
        'file_size',
        'status',
        'raw_text',
        'parsed_data',
        'parse_error',
    ];

    protected $casts = [
        'parsed_data' => 'array',   // auto-decode JSON
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function jobDescription()
    {
        return $this->belongsTo(JobDescription::class);
    }

    public function score()
    {
        return $this->hasOne(Score::class);
    }
}
