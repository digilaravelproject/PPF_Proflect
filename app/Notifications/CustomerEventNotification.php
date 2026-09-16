<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerEventNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $event,
        public string $title,
        public string $message,
        public ?string $url = null,
        public ?string $action = null,
        public array $links = [],
        public bool $sendMail = true,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->sendMail ? ['database', 'mail'] : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'action' => $this->action,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->view('emails.customer-notification', [
                'customer' => $notifiable,
                'title' => $this->title,
                'bodyText' => $this->message,
                'url' => $this->url,
                'action' => $this->action,
                'links' => $this->links,
            ]);
    }
}
