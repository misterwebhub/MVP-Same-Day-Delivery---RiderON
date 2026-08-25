<?php

namespace App\Services\Sms;

interface SmsProvider
{
    /**
     * Send a free-form text message. Kept for providers/flows that need an
     * arbitrary message body (e.g. DLT-template providers that inject the
     * text into a registered template variable).
     */
    public function send(string $toPhone, string $message): SmsSendResult;

    /**
     * Send an OTP code specifically. Some providers (e.g. HanuOtp) have a
     * dedicated OTP endpoint that takes the raw code as its own parameter
     * rather than a pre-built sentence, so this is a first-class method
     * instead of every OTP-only provider having to regex a message apart.
     * Generic providers (Mock, Msg91) simply build the same sentence
     * OtpService used to build itself and delegate to send().
     */
    public function sendOtp(string $toPhone, string $otp, string $purpose): SmsSendResult;
}
