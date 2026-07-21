<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_registers_site_and_receives_credentials_once(): void
    {
        $admin = User::factory()->create(); // role=admin, status=active за замовчуванням

        $response = $this->actingAs($admin)->postJson('/admin/sites', [
            'name' => 'Acme',
            'domain' => 'https://Acme.Example.com/',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('site.status', 'pending')          // FR-013, US1 сценарій 1
            ->assertJsonPath('site.domain', 'acme.example.com') // нормалізовано (FR-006)
            ->assertJsonPath('site.last_seen_at', null)
            ->assertJsonStructure(['credentials' => ['site_id', 'signing_secret', 'sig_version']]);

        // Секрет — 256-bit (64 hex).
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $response->json('credentials.signing_secret'));

        $this->assertDatabaseHas('sites', ['domain' => 'acme.example.com']);
        $this->assertDatabaseHas('site_statuses', ['status' => 'pending']);
    }

    public function test_manager_is_forbidden(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)
            ->postJson('/admin/sites', ['name' => 'X', 'domain' => 'x.example.com'])
            ->assertStatus(403);
    }

    public function test_guest_is_forbidden(): void
    {
        $this->postJson('/admin/sites', ['name' => 'X', 'domain' => 'x.example.com'])
            ->assertStatus(403);
    }
}
