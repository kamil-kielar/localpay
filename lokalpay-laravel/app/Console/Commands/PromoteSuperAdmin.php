<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteSuperAdmin extends Command
{
    protected $signature = 'lokalpay:promote-super-admin {email} {--revoke}';
    protected $description = 'Nadaje lub odbiera rolę super administratora zweryfikowanemu kontu.';

    public function handle(): int
    {
        $user = User::query()->where('email', strtolower((string) $this->argument('email')))->first();
        if (!$user) { $this->error('Nie znaleziono użytkownika.'); return self::FAILURE; }
        if (!$this->option('revoke') && !$user->hasVerifiedEmail()) { $this->error('E-mail użytkownika nie jest zweryfikowany.'); return self::FAILURE; }
        $user->forceFill(['is_super_admin' => !$this->option('revoke')])->save();
        $this->info($this->option('revoke') ? 'Uprawnienia odebrane.' : 'Uprawnienia nadane.');
        return self::SUCCESS;
    }
}
