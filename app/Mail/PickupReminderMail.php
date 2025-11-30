<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PickupReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⏰ Recordatorio: Recoge tu pedido en 10 minutos - KAVAS-LUNCH',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pickup-reminder',
            text: 'emails.pickup-reminder-text',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
