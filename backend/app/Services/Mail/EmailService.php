<?php

namespace App\Services\Mail;

use App\Mail\GenericMail;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Single front door for sending any email from the backend.
 *
 * Goal: adding a new email in the future should be "add a Blade view +
 * call one method here", not "wire up a new Mailable, figure out queueing,
 * add error handling". Callers (controllers, jobs, listeners) never touch
 * Illuminate\Support\Facades\Mail directly — they go through this class so
 * error handling, logging, and (later) things like a "disable outbound
 * email in this environment" switch live in exactly one place.
 *
 * Usage:
 *   app(EmailService::class)->send(
 *       'user@example.com',
 *       'Your RiderON order has shipped',
 *       'emails.order-shipped',
 *       ['order' => $order],
 *   );
 *
 * To send an existing, custom Mailable instead of the generic view-based
 * one (e.g. something with bespoke attachment logic), use sendMailable().
 */
class EmailService
{
    /**
     * Send a templated email built from a Blade view, synchronously.
     *
     * @param  string|array<int, string>  $to
     * @param  array<string, mixed>  $data
     * @param  array<int, array{path: string, name?: string, mime?: string}>  $attachments
     */
    public function send(
        string|array $to,
        string $subject,
        string $view,
        array $data = [],
        array $attachments = [],
    ): bool {
        return $this->sendMailable($to, new GenericMail($subject, $view, $data, $attachments));
    }

    /**
     * Same as send(), but pushed onto the queue instead of sent inline.
     * Prefer this for anything triggered from a user-facing request so a
     * slow/broken mail server can't add latency to the API response.
     *
     * @param  string|array<int, string>  $to
     * @param  array<string, mixed>  $data
     * @param  array<int, array{path: string, name?: string, mime?: string}>  $attachments
     */
    public function queue(
        string|array $to,
        string $subject,
        string $view,
        array $data = [],
        array $attachments = [],
    ): void {
        Mail::to($to)->queue(new GenericMail($subject, $view, $data, $attachments));
    }

    /**
     * Send a pre-built Mailable (escape hatch for emails that need custom
     * logic beyond "render a view"), through the same error handling as
     * every other email in the app.
     *
     * @param  string|array<int, string>  $to
     */
    public function sendMailable(string|array $to, Mailable $mailable): bool
    {
        try {
            Mail::to($to)->send($mailable);

            return true;
        } catch (Throwable $e) {
            Log::error('[EmailService] Failed to send email', [
                'to' => $to,
                'mailable' => $mailable::class,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
