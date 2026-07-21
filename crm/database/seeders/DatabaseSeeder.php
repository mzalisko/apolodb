<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\User;
use App\Services\CredentialService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@databridge.local'],
            [
                'name' => 'Адміністратор',
                'password' => 'password',   // cast 'hashed' захешує один раз (плейнтекст!)
                'role' => 'admin',
                'status' => 'active',
            ],
        );

        // Демо-сайти з різними статусами (для перегляду інтерфейсу).
        $demo = [
            ['Ромашка', 'romashka.ua', 'online', now()->subSeconds(30)],
            ['Барвінок', 'barvinok.ua', 'online', now()->subSeconds(50)],
            ['Соняшник', 'sonyashnyk.ua', 'online', now()->subSeconds(15)],
            ['Волошка', 'voloshka.ua', 'offline', now()->subMinutes(12)],
            ['Мальва', 'malva.ua', 'pending', null],
            ['Калина', 'kalyna.ua', 'inactive', now()->subMinutes(40)],
        ];

        foreach ($demo as [$name, $domain, $status, $lastSeen]) {
            if (Site::where('domain', $domain)->exists()) {
                continue;
            }

            $site = Site::create([
                'name' => $name,
                'domain' => $domain,
                'site_identifier' => CredentialService::generateSiteIdentifier(),
                'deactivated_at' => $status === 'inactive' ? now() : null,
            ]);

            $site->status()->create([
                'status' => $status,
                'last_seen_at' => $lastSeen,
                'last_status_change_at' => now(),
                'updated_at' => now(),
            ]);

            CredentialService::issue($site);
        }
    }
}
