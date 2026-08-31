<?php

namespace App\Console\Commands;

use App\Jobs\SendRentReminder;
use App\Models\Obligation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendRentReminders extends Command
{
    protected $signature = 'lokalpay:send-rent-reminders';
    protected $description = 'Aktualizuje zaległości i kolejkuje dzienne przypomnienia o czynszu.';
    public function handle(): int
    {
        Obligation::query()->whereIn('status', ['due', 'partial'])->whereDate('due_date', '<', today())->update(['status' => 'overdue']);
        $count = 0;
        Obligation::query()->whereIn('status', ['due', 'overdue', 'partial'])
            ->whereDate('due_date', '<=', today()->addDays(3))
            ->with('lease')->chunkById(200, function ($obligations) use (&$count): void {
                foreach ($obligations as $obligation) {
                    if (!$obligation->lease->tenant_user_id) continue;
                    if (!Cache::add("rent-reminder:{$obligation->id}:".today()->toDateString(), true, now()->addDays(2))) continue;
                    SendRentReminder::dispatch($obligation->id);
                    $count++;
                }
            });
        $this->info("Zakolejkowano {$count} przypomnień.");
        return self::SUCCESS;
    }
}
