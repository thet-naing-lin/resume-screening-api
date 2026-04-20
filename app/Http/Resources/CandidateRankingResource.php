<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// take a `Resume` model (with its candidate and score) and turn it into a clean JSON object exactly how the frontend needs it.
class CandidateRankingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'resume_id'         => $this->id,
            'original_filename' => $this->original_filename,
            'file_type'         => $this->file_type,
            'resume_status'     => $this->status,
            'uploaded_at'       => $this->created_at->toDateTimeString(),

            // Candidate info
            'candidate' => [
                'id'               => $this->candidate?->id,
                'name'             => $this->candidate?->name ?? 'Unknown',
                'email'            => $this->candidate?->email ?? 'N/A',
                'phone'            => $this->candidate?->phone ?? 'N/A',
                'skills'           => $this->candidate?->extracted_skills ?? [],
                'experience_years' => $this->candidate?->experience_years,
            ],

            // Score info
            'score' => [
                'tfidf_score'    => $this->score?->tfidf_score ?? 0,
                'semantic_score' => $this->score?->semantic_score ?? 0,
                'final_score'    => $this->score?->final_score ?? 0,
                'status'         => $this->score?->status ?? 'under_review',
            ],
        ];
    }
}
