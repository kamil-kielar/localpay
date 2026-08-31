<?php

namespace App\Notifications;

use App\Models\Obligation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceiptNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(private Obligation $obligation) {}
    public function via(object $notifiable): array { return ['database', 'mail']; }
    public function toArray(object $notifiable): array
    {
        return ['title' => 'Płatność zaksięgowana', 'body' => "Wpłata dla okresu {$this->obligation->period} została zapisana.", 'obligation_id' => $this->obligation->public_id];
    }
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Potwierdzenie płatności LokalPay')->line($this->toArray($notifiable)['body']);
    }
}
