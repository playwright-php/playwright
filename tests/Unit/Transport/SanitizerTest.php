<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Transport\Sanitizer;

#[CoversClass(Sanitizer::class)]
class SanitizerTest extends TestCase
{
    #[Test]
    public function itSanitizesNullValues(): void
    {
        $result = Sanitizer::sanitizeParams(null);

        $this->assertNull($result);
    }

    #[Test]
    public function itSanitizesScalarValues(): void
    {
        $this->assertEquals(42, Sanitizer::sanitizeParams(42));
        $this->assertEquals(3.14, Sanitizer::sanitizeParams(3.14));
        $this->assertTrue(Sanitizer::sanitizeParams(true));
    }

    #[Test]
    public function itSanitizesArraysWithSensitiveKeys(): void
    {
        $params = [
            'username' => 'john',
            'password' => 'secret123',
            'api_key' => 'abc123def456',
            'data' => 'normal data',
        ];

        $result = Sanitizer::sanitizeParams($params);

        $this->assertEquals('john', $result['username']);
        $this->assertEquals('[REDACTED]', $result['password']);
        $this->assertEquals('[REDACTED]', $result['api_key']);
        $this->assertEquals('normal data', $result['data']);
    }

    #[Test]
    public function itSanitizesNestedArrays(): void
    {
        $params = [
            'user' => [
                'name' => 'john',
                'secret' => 'hidden',
                'config' => [
                    'token' => 'jwt_token_here',
                    'setting' => 'value',
                ],
            ],
        ];

        $result = Sanitizer::sanitizeParams($params);

        $this->assertEquals('john', $result['user']['name']);
        $this->assertEquals('[REDACTED]', $result['user']['secret']);
        $this->assertEquals('[REDACTED]', $result['user']['config']['token']);
        $this->assertEquals('value', $result['user']['config']['setting']);
    }

    #[Test]
    public function itSanitizesObjects(): void
    {
        $object = (object) [
            'username' => 'john',
            'password' => 'secret123',
            'data' => 'normal',
        ];

        $result = Sanitizer::sanitizeParams($object);

        $this->assertEquals('john', $result->username);
        $this->assertEquals('[REDACTED]', $result->password);
        $this->assertEquals('normal', $result->data);
    }

    #[Test]
    public function itDetectsSensitiveKeys(): void
    {
        $sensitiveParams = [
            'PASSWORD' => 'should_be_hidden',
            'user_token' => 'should_be_hidden',
            'session_id' => 'should_be_hidden',
            'private_key' => 'should_be_hidden',
            'authorization_header' => 'should_be_hidden',
        ];

        $result = Sanitizer::sanitizeParams($sensitiveParams);

        foreach ($result as $value) {
            $this->assertEquals('[REDACTED]', $value);
        }
    }

    #[Test]
    public function itSanitizesStrings(): void
    {
        $jwt = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.dozjgNryP4J3jVmNHl0w5N_XgL0n3I9PlFUP0THsR8U';
        $result = Sanitizer::sanitizeParams($jwt);
        $this->assertEquals('[JWT_TOKEN]', $result);

        $apiKey = '1234567890abcdef1234567890abcdef';
        $result = Sanitizer::sanitizeParams($apiKey);
        $this->assertEquals('[API_KEY]', $result);

        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $result = Sanitizer::sanitizeParams($uuid);
        $this->assertEquals('[UUID]', $result);
    }

    #[Test]
    public function itSanitizesUrls(): void
    {
        $url = 'https://user:password@example.com:8080/path?query=value#fragment';
        $result = Sanitizer::sanitizeUrl($url);

        $this->assertEquals('https://[REDACTED]@example.com:8080/path?query=value#fragment', $result);
    }

    #[Test]
    public function itSanitizesUrlsWithoutUserInfo(): void
    {
        $url = 'https://example.com/path';
        $result = Sanitizer::sanitizeUrl($url);

        $this->assertEquals('https://example.com/path', $result);
    }

    #[Test]
    public function itHandlesInvalidUrls(): void
    {
        $invalidUrl = 'not-a-valid-url';
        $result = Sanitizer::sanitizeUrl($invalidUrl);

        $this->assertEquals('not-a-valid-url', $result);
    }

    #[Test]
    public function itSanitizesCommonPatterns(): void
    {
        $text = 'Authorization: Bearer abc123def456 and Basic dXNlcjpwYXNzd29yZA==';
        $result = Sanitizer::sanitizeParams($text);

        $this->assertStringContainsString('Bearer [REDACTED]', $result);
        $this->assertStringContainsString('Basic [REDACTED]', $result);
    }

    #[Test]
    public function itPreservesNormalData(): void
    {
        $params = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
            'active' => true,
            'scores' => [85, 92, 78],
            'metadata' => [
                'created' => '2023-01-01',
                'updated' => '2023-12-31',
            ],
        ];

        $result = Sanitizer::sanitizeParams($params);

        $this->assertEquals($params, $result);
    }
}
