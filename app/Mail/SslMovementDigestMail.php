<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SslMovementDigestMail extends BaseMailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private User $worker,
        private Collection $movements,
        private string $batchReference,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Snipe-SSL | Resumen de entregas y devoluciones ('.$this->movements->count().')',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.markdown.ssl-movement-digest',
            with: [
                'worker' => $this->worker,
                'movements' => $this->movements,
                'batchReference' => $this->batchReference,
                'sslSettings' => Setting::getSettings(),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
