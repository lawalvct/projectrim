<?php

namespace App\Mail;

use App\Models\PayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayoutApprovedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PayoutRequest $payout) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your payout request has been approved',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payout-approved',
        );
    }
}
