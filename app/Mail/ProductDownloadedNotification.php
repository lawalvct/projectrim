<?php

namespace App\Mail;

use App\Models\Download;
use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductDownloadedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Product $product,
        public User $downloader,
        public Download $download,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your product was downloaded: '.$this->product->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.product-downloaded',
        );
    }
}
