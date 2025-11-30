<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationExpiredMail extends Mailable
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
            subject: '❌ Tu reserva ha expirado - KAVAS-LUNCH',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservation-expired',
            text: 'emails.reservation-expired-text',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
