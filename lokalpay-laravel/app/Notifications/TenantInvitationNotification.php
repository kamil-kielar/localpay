<?php

namespace App\Notifications;

use App\Models\Lease;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(private Lease $lease, private string $url) {}
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Zaproszenie do portalu najemcy LokalPay')
            ->greeting("Dzień dobry, {$this->lease->tenant_name}!")
            ->line("Otrzymujesz dostęp do rozliczeń nieruchomości {$this->lease->property->name}.")
            ->action('Aktywuj dostęp', $this->url)
            ->line('Link jest ważny 7 dni. LokalPay nie wysyła wiadomości SMS.');
    }
}
