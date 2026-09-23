<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\WarrantyCode;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessfulMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment, public WarrantyCode $warrantyCode) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Proflect subscription and warranty code', replyTo: ['contact@proflect.com.au']);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-successful');
    }
}
