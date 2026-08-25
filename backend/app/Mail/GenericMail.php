<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A single reusable Mailable for every outgoing email in the app.
 *
 * Rather than hand-rolling a new Mailable class per email (WelcomeMail,
 * PasswordResetMail, RefundApprovedMail, ...), every email is expressed as
 * "render this Blade view with this data", and this class is the one and
 * only Mailable that does the rendering. To add a new email in the future:
 *   1. Add a Blade view under resources/views/emails/{name}.blade.php
 *   2. Call EmailService::send($to, $subject, "emails.{name}", $data)
 * No new PHP class required for the common case. Drop down to a dedicated
 * Mailable only if an email needs custom envelope logic (e.g. attachments
 * built from a queued job's own state).
 */
class GenericMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array{path: string, name?: string, mime?: string}>  $attachmentSpecs
     */
    public function __construct(
        private readonly string $subjectLine,
        private readonly string $viewName,
        private readonly array $data = [],
        private readonly array $attachmentSpecs = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: $this->viewName, with: $this->data);
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return array_map(
            fn (array $a) => \Illuminate\Mail\Mailables\Attachment::fromPath($a['path'])
                ->as($a['name'] ?? basename($a['path']))
                ->withMime($a['mime'] ?? 'application/octet-stream'),
            $this->attachmentSpecs,
        );
    }
}
