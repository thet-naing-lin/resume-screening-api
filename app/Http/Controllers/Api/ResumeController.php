<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResumeRequest;
use App\Models\Resume;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ResumeController extends Controller
{
    public function index()
    {
        $resumes = Resume::with(['candidate', 'jobDescription', 'score', 'uploader'])
            ->where('uploaded_by', auth()->id())
            ->latest()
            ->get();

        return response()->json(['data' => $resumes]);
    }

    public function store(StoreResumeRequest $request)
    {
        $uploaded = [];   // collect results for each file
        $failed   = [];   // collect any individual failures

        foreach ($request->file('resume_files') as $file) {
            try {
                $storedFilename = Str::uuid() . '.' . $file->getClientOriginalExtension();

                // Save file to private storage
                $file->storeAs('resumes', $storedFilename, 'private');

                // Create DB record
                $resume = Resume::create([
                    'job_description_id' => $request->job_description_id,
                    'uploaded_by'        => auth()->id(),
                    'candidate_id'       => null,
                    'original_filename'  => $file->getClientOriginalName(),
                    'stored_filename'    => $storedFilename,
                    'file_type'          => $file->getClientOriginalExtension(),
                    'file_size'          => $file->getSize(),
                    'status'             => 'uploaded',
                ]);

                // TODO: ProcessResumeJob::dispatch($resume);

                $uploaded[] = [
                    'id'       => $resume->id,
                    'filename' => $file->getClientOriginalName(),
                    'status'   => 'uploaded',
                ];
            } catch (\Exception $e) {
                // One file failed — don't stop the whole loop
                $failed[] = [
                    'filename' => $file->getClientOriginalName(),
                    'error'    => 'Failed to save this file.',
                ];
            }
        }

        return response()->json([
            'message'  => count($uploaded) . ' resume(s) uploaded successfully.',
            'uploaded' => $uploaded,
            'failed'   => $failed,
        ], 201);
    }

    public function destroy(Resume $resume)
    {
        // 1. Ownership check — only the uploader can delete
        if ($resume->uploaded_by !== auth()->id()) {
            return response()->json([
                'message' => 'You are not allowed to delete this resume.',
            ], 403);
        }

        // 2. Status guard — can't delete if it's being processed
        $deletableStatuses = ['uploaded', 'failed'];

        if (!in_array($resume->status, $deletableStatuses)) {
            return response()->json([
                'message' => 'Cannot delete a resume that is currently being processed or has been scored.',
            ], 422);
        }

        // 3. Delete the physical file from private storage
        Storage::disk('private')->delete('resumes/' . $resume->stored_filename);

        // 4. Save filename for the success message before deleting
        $filename = $resume->original_filename;

        // 5. Delete the DB record (scores cascade automatically)
        $resume->delete();

        return response()->json([
            'message' => "\"{$filename}\" has been deleted.",
        ]);
    }
}
