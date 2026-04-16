<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResumeRequest;
use App\Models\Resume;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ResumeController extends Controller
{
    public function store(StoreResumeRequest $request)
    {
        $file = $request->file('resume_file');

        // Generate unique stored filename
        $storedFilename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        // Store in private disk (not public)
        // People can’t download the file directly by URL
        // you can control access later via a controller.
        $path = $file->storeAs('resumes', $storedFilename, 'private');

        // store in DB
        $resume = Resume::create([
            'job_description_id' => $request->job_description_id,
            'uploaded_by'        => auth()->id(),
            'candidate_id'       => null,          // filled later after parsing
            'original_filename'  => $file->getClientOriginalName(),
            'stored_filename'    => $storedFilename,
            'file_type'          => $file->getClientOriginalExtension(),
            'file_size'          => $file->getSize(),
            'status'             => 'uploaded',
        ]);

        // Dispatch background job for parsing (Sprint 2 next step)
        // ProcessResumeJob::dispatch($resume);

        return response()->json([
            'message' => 'Resume uploaded successfully.',
            'data'    => $resume,
        ], 201);
    }

    public function index()
    {
        $resumes = Resume::with(['candidate', 'jobDescription', 'score', 'uploader'])
            ->where('uploaded_by', auth()->id())
            ->latest()
            ->get();

        return response()->json(['data' => $resumes]);
    }
}
