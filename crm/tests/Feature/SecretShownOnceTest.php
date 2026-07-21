<?php

namespace Tests\Feature;

use App\Models\SiteCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecretShownOnceTest extends TestCase
{
    use RefreshDatabase;

    public function test_secret_is_encrypted_at_rest_and_returned_only_in_registration_response(): void
    {
        $admin = User::factory()->create();

        $secret = $this->actingAs($admin)
            ->postJson('/admin/sites', ['name' => 'A', 'domain' => 'enc.example.com'])
            ->assertStatus(201)
            ->json('credentials.signing_secret');

        // Сире значення у БД — шифротекст, НЕ plaintext (Конституція v2.0.1, encrypted-at-rest).
        $rawStored = DB::table('site_credentials')->value('secret_encrypted');
        $this->assertNotSame($secret, $rawStored);
        $this->assertNotEmpty($rawStored);

        // Модель дешифрує назад у той самий секрет (для переобчислення HMAC).
        $credential = SiteCredential::firstOrFail();
        $this->assertSame($secret, $credential->secret());
        $this->assertSame('active', $credential->state);
    }
}
