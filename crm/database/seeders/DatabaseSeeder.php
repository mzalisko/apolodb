<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Site;
use App\Models\User;
use App\Services\CredentialService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@databridge.local'],
            [
                'name' => 'Адміністратор',
                'password' => 'password',   // cast 'hashed' захешує один раз (плейнтекст!)
                'role' => 'admin',
                'status' => 'active',
            ],
        );

        // Групи — точно як у дизайні (design/CRM v2.dc.html · buildData).
        $groups = collect(['Сервіси', 'Київ', 'Медицина', 'Авто', 'Топ', 'Ритейл'])
            ->mapWithKeys(fn (string $n) => [$n => Group::firstOrCreate(['name' => $n])])
            ->all();

        // Дерево сайтів із дизайну. Статус зв'язності виводимо з (design.status, design.sync):
        //   inactive → inactive · sync=err → offline · sync=pending → pending · інакше → online.
        // Формат: [назва, домен, статус, [групи], last_seen, [піддомени...]]
        $tree = [
            ['Ремонт Техніки', 'remont-technika.com.ua', 'online', ['Сервіси', 'Київ'], now()->subSeconds(30), [
                ['Львівська філія', 'lviv.remont-technika.com.ua', 'online', now()->subSeconds(40)],
                ['Одеська філія', 'odesa.remont-technika.com.ua', 'pending', null],
            ]],
            ['Стоматологія Київ', 'stomatologia-kyiv.com', 'online', ['Медицина'], now()->subSeconds(20), []],
            ['Прокат Авто', 'prokat-avto.ua', 'offline', ['Авто', 'Топ'], now()->subMinutes(12), [
                ['Аеропорт', 'airport.prokat-avto.ua', 'online', now()->subSeconds(25)],
            ]],
            ['Доставка Квітів', 'dostavka-kvitiv.ua', 'inactive', ['Ритейл', 'Київ'], now()->subMinutes(40), []],
            ['Юридична Консультація', 'yur-consult.com.ua', 'online', ['Сервіси'], now()->subSeconds(55), []],
            ['Клінінг Плюс', 'klining-plus.com', 'pending', ['Сервіси', 'Топ'], null, [
                ['Корпоративний', 'b2b.klining-plus.com', 'online', now()->subSeconds(35)],
            ]],
            ['Автозапчастини', 'avto-zapchastyny.com.ua', 'inactive', ['Авто'], now()->subMinutes(50), []],
        ];

        foreach ($tree as [$name, $domain, $status, $groupNames, $lastSeen, $subs]) {
            $site = $this->makeSite($name, $domain, $status, $lastSeen, null);

            $ids = collect($groupNames)->map(fn (string $n) => $groups[$n]->id)->all();
            $site->groups()->syncWithoutDetaching($ids);

            foreach ($subs as [$sName, $sDomain, $sStatus, $sLastSeen]) {
                $this->makeSite($sName, $sDomain, $sStatus, $sLastSeen, $site->id);
            }
        }

        // Демо-обране адміна — точно як на скріншоті дизайну (сайдбар «Обране»).
        $favDomains = ['remont-technika.com.ua', 'prokat-avto.ua', 'yur-consult.com.ua'];
        $favSiteIds = Site::whereIn('domain', $favDomains)->pluck('id')->all();
        $admin->favoriteSites()->syncWithoutDetaching($favSiteIds);

        if ($topGroup = ($groups['Топ'] ?? null)) {
            $admin->favoriteGroups()->syncWithoutDetaching([$topGroup->id]);
        }
    }

    /** Створює сайт (або піддомен) зі статусом і виданим секретом; ідемпотентно за доменом. */
    private function makeSite(string $name, string $domain, string $status, $lastSeen, ?int $parentId): Site
    {
        if ($existing = Site::where('domain', $domain)->first()) {
            return $existing;
        }

        $site = Site::create([
            'name' => $name,
            'domain' => $domain,
            'parent_site_id' => $parentId,
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

        return $site;
    }
}
