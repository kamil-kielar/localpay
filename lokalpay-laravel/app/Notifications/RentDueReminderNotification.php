<?php

namespace App\Notifications;

use App\Models\Obligation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RentDueReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(private Obligation $obligation) {}
    public function via(object $notifiable): array { return ['database', 'mail']; }
    public function toArray(object $notifiable): array
    {
        return ['title' => 'Przypomnienie o czynszu', 'body' => "Należność {$this->obligation->period} ma termin {$this->obligation->due_date->format('d.m.Y')}.", 'obligation_id' => $this->obligation->public_id];
    }
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Przypomnienie o czynszu')
            ->line($this->toArray($notifiable)['body'])->action('Otwórz portal', route('tenant.portal'));
    }
}
