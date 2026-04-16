<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreResumeRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;   // ← make sure this is true, not false!
    }

    public function rules(): array
    {
        return [
            'resume_file'        => 'required|file|mimes:pdf,docx|max:5120', // 5MB

            // HR can’t upload a resume for a job that doesn’t exist in the JD table
            'job_description_id' => 'required|exists:job_descriptions,id',
        ];
    }

    public function messages(): array
    {
        return [
            'resume_file.mimes' => 'Only PDF and DOCX files are accepted.',
            'resume_file.max'   => 'File must not exceed 5MB.',
        ];
    }
}
