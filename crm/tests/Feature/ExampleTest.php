<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_admin(): void
    {
        $this->get('/')->assertRedirect('/admin');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertStatus(200)->assertSee('DataBridge');
    }
}
