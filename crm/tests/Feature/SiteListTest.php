<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesSites;
use Tests\TestCase;

class SiteListTest extends TestCase
{
    use MakesSites, RefreshDatabase;

    public function test_list_shows_sites_with_status_and_last_update(): void
    {
        $admin = User::factory()->create();
        $this->makeSiteWithStatus('online', ['name' => 'Alpha']);

        $response = $this->actingAs($admin)->getJson('/admin/sites');

        $response->assertStatus(200)
            ->assertJsonPath('sites.0.name', 'Alpha')
            ->assertJsonPath('sites.0.status', 'online')      // FR-013/016
            ->assertJsonPath('counts.total', 1);

        $this->assertNotNull($response->json('sites.0.last_seen_at')); // час останнього оновлення (A-7)
    }

    public function test_list_requires_admin(): void
    {
        $this->getJson('/admin/sites')->assertStatus(403);
        $this->actingAs(User::factory()->manager()->create())
            ->getJson('/admin/sites')->assertStatus(403);
    }
}
