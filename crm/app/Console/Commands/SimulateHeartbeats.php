<?php

namespace App\Console\Commands;

use App\Models\SiteStatus;
use Illuminate\Console\Command;

/**
 * Демо-симулятор живого фліту.
 *
 * Немає реального WordPress-плагіна, що звітує, тож online-сайти показували б
 * застарілий last_seen («2 год тому» при статусі «Активний» — суперечність).
 * Ця команда щохвилини оновлює last_seen для online-сайтів, ніби плагіни звітують.
 * Offline/pending/inactive НЕ чіпаємо — вони лишаються у своїх станах.
 */
class SimulateHeartbeats extends Command
{
    protected $signature = 'databridge:simulate-heartbeats';

    protected $description = 'Демо: оновлює last_seen для online-сайтів (імітація живого фліту)';

    public function handle(): int
    {
        $n = SiteStatus::where('status', 'online')->update([
            'last_seen_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("Оновлено last_seen для {$n} online-сайтів.");

        return self::SUCCESS;
    }
}
