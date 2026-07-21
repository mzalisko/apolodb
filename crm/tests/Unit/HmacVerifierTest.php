<?php

namespace Tests\Unit;

use App\Services\HmacVerifier;
use App\Support\CanonicalRequest;
use Tests\TestCase;

class HmacVerifierTest extends TestCase
{
    public function test_canonical_string_has_exact_7_field_format(): void
    {
        $body = '{"site_id":"sid","status":"online","timestamp":123,"nonce":"n"}';

        $expected = "v1\nPOST\n/v1/heartbeat\n".hash('sha256', $body)."\nsid\n123\nn";

        $this->assertSame($expected, CanonicalRequest::build('v1', 'POST', '/v1/heartbeat', $body, 'sid', 123, 'n'));
    }

    public function test_method_is_uppercased(): void
    {
        $lower = CanonicalRequest::build('v1', 'post', '/p', 'b', 'sid', 1, 'n');
        $upper = CanonicalRequest::build('v1', 'POST', '/p', 'b', 'sid', 1, 'n');

        $this->assertSame($upper, $lower);
    }

    public function test_sign_produces_prefixed_hex_and_verifies_constant_time(): void
    {
        $canonical = CanonicalRequest::build('v1', 'POST', '/v1/heartbeat', '{}', 'sid', 123, 'n');
        $signature = HmacVerifier::sign($canonical, 'secret');

        $this->assertMatchesRegularExpression('/^sha256=[0-9a-f]{64}$/', $signature);
        $this->assertTrue(HmacVerifier::verify($signature, $canonical, 'secret'));
        $this->assertFalse(HmacVerifier::verify($signature, $canonical, 'wrong-secret'));
        $this->assertFalse(HmacVerifier::verify('sha256=deadbeef', $canonical, 'secret'));
    }
}
