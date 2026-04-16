<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
    ];

    public function resumes()
    {
        return $this->hasMany(Resume::class);
    }

    // Access scores THROUGH resumes (hasManyThrough)
    public function scores()
    {
        return $this->hasManyThrough(Score::class, Resume::class);
    }
}
