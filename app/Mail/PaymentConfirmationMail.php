<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Confirmación de pago (RF-014): Yape/Plin o tarjeta al confirmar; efectivo al pagar en caja
class PaymentConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $payment;
    public $reservation;
    public $methodLabel;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
        $this->reservation = $payment->reservation;
        $this->methodLabel = Payment::methodLabel($payment->method);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pago confirmado - KAVAS-LUNCH',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-confirmation',
            text: 'emails.payment-confirmation-text',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
