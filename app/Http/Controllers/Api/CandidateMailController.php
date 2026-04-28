<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\InterviewInvitationMail;
use App\Mail\RejectionNoticeMail;
use App\Models\Resume;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CandidateMailController extends Controller
{
    /**
     * Return pre-filled default content for a mail type
     */
    public function template(Request $request)
    {
        $request->validate([
            'type'           => 'required|in:interview,rejection',
            'candidate_name' => 'required|string',
            'job_title'      => 'required|string',
        ]);

        $name     = $request->candidate_name;
        $job      = $request->job_title;
        $company  = config('app.name', 'Our Company');

        if ($request->type === 'interview') {
            return response()->json([
                'subject' => "Interview Invitation – {$job} Position",
                'body'    =>
                "Dear {$name},

Thank you for applying for the {$job} position at {$company}.

We are pleased to inform you that after reviewing your application, we would like to invite you to the next stage of our selection process — an interview.

Please reply to this email to confirm your availability, and we will arrange a suitable time.

We look forward to speaking with you.

Kind regards,
HR Team
{$company}",
            ]);
        }

        return response()->json([
            'subject' => "Your Application for {$job}",
            'body'    =>
            "Dear {$name},

Thank you for taking the time to apply for the {$job} position at {$company} and for your interest in joining our team.

After careful consideration, we regret to inform you that we will not be moving forward with your application at this time. This was a difficult decision as we received many strong applications.

We appreciate the effort you put into your application and encourage you to apply for future opportunities that match your skills and experience.

We wish you the best in your job search.

Kind regards,
HR Team
{$company}",
        ]);
    }

    /**
     * Send the mail
     */
    public function send(Request $request)
    {
        $request->validate([
            'resume_id' => 'required|exists:resumes,id',
            'type'      => 'required|in:interview,rejection',
            'subject'   => 'required|string|max:255',
            'body'      => 'required|string|max:5000',
            'to_email'  => 'nullable|email',
        ]);

        $resume    = Resume::with('candidate')->findOrFail($request->resume_id);
        $candidate = $resume->candidate;

        // Use HR's corrected email if provided, fallback to stored one
        $recipientEmail = $request->filled('to_email')
            ? $request->to_email
            : $candidate->email;

        if (!$recipientEmail) {
            return response()->json([
                'message' => 'No email address provided.',
            ], 422);
        }

        $mailable = $request->type === 'interview'
            ? new InterviewInvitationMail($request->subject, $request->body, $candidate->name)
            : new RejectionNoticeMail($request->subject, $request->body, $candidate->name);

        Mail::to($recipientEmail)->send($mailable);

        // Audit log
        AuditLogger::log("candidate.email_sent", $resume, [
            'type'           => $request->type,
            'to'             => $recipientEmail,
            'original_email' => $candidate->email,
            'corrected'      => $recipientEmail !== $candidate->email
                ? 'Corrected email used'
                : 'Original candidate email used',
            'candidate_name' => $candidate->name,
            'subject'        => $request->subject,
        ]);

        return response()->json([
            'message' => "Email sent successfully to {$recipientEmail}",
        ]);
    }

    /**
     * Send mail to all candidates of a given status (shortlisted / rejected)
     */
    public function sendBulk(Request $request)
    {
        $request->validate([
            'status'             => 'required|in:shortlisted,rejected',
            'subject'            => 'required|string|max:255',
            'body'               => 'required|string|max:5000',
            'job_description_id' => 'nullable|exists:job_descriptions,id',
            'overrides'          => 'nullable|array',
            'overrides.*.resume_id'     => 'required|integer',
            'overrides.*.override_email' => 'nullable|email',
        ]);

        // Build override lookup: resume_id → email
        $overrideMap = collect($request->overrides ?? [])
            ->keyBy('resume_id')
            ->map(fn($o) => $o['override_email']);

        // We need to join with scores to filter by status, but also allow optional filtering by job description
        $resumes = Resume::with('candidate')
            ->whereHas('score', function ($q) use ($request) {
                $q->where('status', $request->status);

                // filter by job if provided
                if ($request->filled('job_description_id')) {
                    $q->where('job_description_id', $request->job_description_id);
                }
            })
            ->when($request->filled('job_description_id'), function ($q) use ($request) {
                $q->where('job_description_id', $request->job_description_id);
            })
            ->when(!auth()->user()->hasAnyRole(['admin', 'super_admin']), function ($q) {
                $q->where('uploaded_by', auth()->id()); // HR scope
            })
            ->get();

        if ($resumes->isEmpty()) {
            return response()->json([
                'message' => 'No candidates found with status: ' . $request->status,
            ], 404);
        }

        $sent   = [];
        $failed = [];

        foreach ($resumes as $index => $resume) {
            $candidate = $resume->candidate;

            // Determine actual recipient — override takes priority
            $recipientEmail = $overrideMap[$resume->id] ?? $candidate->email;

            if (!$recipientEmail) {
                $failed[] = [
                    'resume_id'      => $resume->id,
                    'candidate_name' => $candidate?->name ?? 'Unknown',
                    'reason'         => 'No email on record',
                ];
                continue;
            }

            try {
                $type = $request->status === 'shortlisted' ? 'interview' : 'rejection';

                $mailable = $type === 'interview'
                    ? new InterviewInvitationMail($request->subject, $request->body, $candidate->name)
                    : new RejectionNoticeMail($request->subject, $request->body, $candidate->name);

                // Delay to avoid Mailtrap rate limit
                if ($index > 0) {
                    sleep(2);
                }

                Mail::to($recipientEmail)->send($mailable);  // send ONCE

                AuditLogger::log("candidate.email_sent", $resume, [
                    'type'           => $type,
                    'to'             => $recipientEmail,
                    'original_email' => $candidate->email,
                    'corrected'      => $recipientEmail !== $candidate->email,
                    'candidate_name' => $candidate->name,
                    'subject'        => $request->subject,
                    'bulk'           => true,
                ]);

                $sent[] = [
                    'resume_id'      => $resume->id,
                    'candidate_name' => $candidate->name,
                    'email'          => $recipientEmail,   // show actual email sent to
                ];
            } catch (\Exception $e) {
                $failed[] = [
                    'resume_id'      => $resume->id,
                    'candidate_name' => $candidate?->name ?? 'Unknown',
                    'reason'         => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => "Bulk email complete. Sent: " . count($sent) . ", Failed: " . count($failed),
            'sent'    => $sent,
            'failed'  => $failed,
        ]);
    }

    /**
     * Preview who will receive the bulk email
     */
    public function bulkPreview(Request $request)
    {
        $request->validate([
            'status'             => 'required|in:shortlisted,rejected',
            'job_description_id' => 'nullable|exists:job_descriptions,id',
        ]);

        $resumes = Resume::with('candidate')
            ->whereHas('score', function ($q) use ($request) {
                $q->where('status', $request->status);
                if ($request->filled('job_description_id')) {
                    $q->where('job_description_id', $request->job_description_id);
                }
            })
            ->when(!auth()->user()->hasAnyRole(['admin', 'super_admin']), function ($q) {
                $q->where('uploaded_by', auth()->id()); // HR scope
            })
            ->get();

        $recipients = $resumes->map(fn($resume) => [
            'resume_id'      => $resume->id,
            'candidate_name' => $resume->candidate?->name ?? 'Unknown',
            'stored_email'   => $resume->candidate?->email ?? null,
            'override_email' => null, // HR can fill this in frontend
        ]);

        return response()->json(['recipients' => $recipients]);
    }
}
