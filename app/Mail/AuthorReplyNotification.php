<?php

namespace App\Mail;

use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuthorReplyNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Message $reply,
        public User $recipient,
        public Message $originalMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You have a new reply on '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.author-reply',
        );
    }
}
