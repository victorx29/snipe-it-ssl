<?php

namespace App\Mail;

use App\Models\Accessory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmación informativa de inscripción SSL/GH/TI.
 * No se dispara desde CheckoutableCheckedOut y nunca acredita asistencia.
 */
class SslTrainingInvitationMail extends BaseMailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private Accessory $training,
        private User $worker,
        private ?User $registrar = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Inscripción a capacitación: '.$this->training->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.markdown.ssl-training-invitation',
            with: [
                'training' => $this->training,
                'worker' => $this->worker,
                'registrar' => $this->registrar,
                'sslSettings' => Setting::getSettings(),
                'ssl_notice_at' => now()->format('d/m/Y H:i'),
                'ssl_area_name' => match ($this->training->category?->name) {
                    'CAPACITACION_SSL' => 'Seguridad y Salud Laboral',
                    'CAPACITACION_GH' => 'Gestión Humana',
                    'CAPACITACION_TI' => 'Tecnología',
                    default => $this->training->category?->name ?? 'No registrada',
                },
                'ssl_status_name' => match ($this->training->ssl_training_status) {
                    'planned' => 'Programada',
                    'completed' => 'Realizada',
                    'cancelled' => 'Cancelada',
                    default => 'No especificado',
                },
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
