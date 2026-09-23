<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Models\WarrantyCode;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WarrantyCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscription $subscription, public WarrantyCode $warrantyCode) {}

    public function build(): self
    {
        return $this->subject('Your Proflect warranty code')
            ->replyTo('contact@proflect.com.au')
            ->view('emails.warranty-code');
    }
}
