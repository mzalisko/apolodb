<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterSiteDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_domain_is_rejected_without_creating_a_second_site(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->postJson('/admin/sites', ['name' => 'A', 'domain' => 'dup.example.com'])
            ->assertStatus(201);

        // Той самий домен у іншому регістрі / зі схемою -> нормалізується до того самого.
        $this->actingAs($admin)
            ->postJson('/admin/sites', ['name' => 'B', 'domain' => 'HTTPS://DUP.example.com/'])
            ->assertStatus(409)
            ->assertJsonPath('error', 'domain_already_registered');

        $this->assertSame(1, Site::where('domain', 'dup.example.com')->count());
    }
}
