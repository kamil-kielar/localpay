<?php

namespace App\Jobs;

use App\Models\Obligation;
use App\Notifications\RentDueReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendRentReminder implements ShouldQueue
{
    use Queueable;
    public int $tries = 3;
    public function __construct(public int $obligationId) {}
    public function handle(): void
    {
        $obligation = Obligation::with('lease.tenant')->find($this->obligationId);
        if (!$obligation || in_array($obligation->status, ['paid', 'void'], true) || !$obligation->lease->tenant) return;
        $obligation->lease->tenant->notify(new RentDueReminderNotification($obligation));
    }
}
