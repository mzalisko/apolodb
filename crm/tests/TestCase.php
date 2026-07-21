<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ізольовані драйвери для тестів (Compose-env може лишати redis; форсуємо тут).
        config()->set('queue.default', 'sync');   // ProcessHeartbeat виконується синхронно
        config()->set('cache.default', 'array');  // nonce-store детермінований у межах тесту
    }
}
