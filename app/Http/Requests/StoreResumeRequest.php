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
            // 'resume_files' is now an array of files
            'resume_files'            => 'required|array|min:1|max:10',
            'resume_files.*'          => 'file|mimes:pdf,docx|max:5120',

            // HR can’t upload a resume for a job that doesn’t exist in the JD table
            'job_description_id'      => 'required|exists:job_descriptions,id',
        ];
    }

    public function messages(): array
    {
        return [
            'resume_files.required'   => 'Please select at least one resume file.',
            'resume_files.max'        => 'You can upload a maximum of 10 resumes at once.',
            'resume_files.*.mimes'    => 'Each file must be a PDF or DOCX.',
            'resume_files.*.max'      => 'Each file must not exceed 5MB.',
            'job_description_id.exists'  => 'The selected job position does not exist.',
        ];
    }
}
