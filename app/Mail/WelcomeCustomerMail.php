<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $customer) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Warranty registered — your Proflect replacement offer is ready');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.welcome-customer');
    }
}
