<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailBody;
    public string $candidateName;

    public function __construct(
        string $subject,
        string $body,
        string $candidateName,
    ) {
        $this->subject      = $subject;   // Mailable's own $subject property
        $this->mailBody     = $body;
        $this->candidateName = $candidateName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.interview-invitation');
    }
}
