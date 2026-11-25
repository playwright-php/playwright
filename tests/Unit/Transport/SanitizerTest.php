<?php

declare(strict_types=1);

/*
 * This file is part of the community-maintained Playwright PHP project.
 * It is not affiliated with or endorsed by Microsoft.
 *
 * (c) 2025-Present - Playwright PHP - https://github.com/playwright-php
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Playwright\Tests\Unit\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Transport\Sanitizer;

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
    public function itKeepsOnlyEnvVariableNames(): void
    {
        $result = Sanitizer::sanitizeParams([
            'headless' => true,
            'env' => ['DATABASE_URL' => 'postgres://user:pass@db/app', 'TZ' => 'UTC'],
        ]);

        $this->assertTrue($result['headless']);
        $this->assertSame(['DATABASE_URL' => '[REDACTED]', 'TZ' => '[REDACTED]'], $result['env']);
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
    #[DataProvider('provideAuthorizationStrings')]
    public function itMasksCompleteAuthorizationCredentials(string $input, string $expected): void
    {
        $this->assertSame($expected, Sanitizer::sanitizeParams($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideAuthorizationStrings(): iterable
    {
        yield 'base64 characters' => ['Bearer demo+secret/token==', 'Bearer [REDACTED]'];
        yield 'leading special character' => ['Bearer +secret/token', 'Bearer [REDACTED]'];
        yield 'tilde' => ['Bearer demo~secret', 'Bearer [REDACTED]'];
        yield 'JWT shape' => ['Bearer first.second.third', 'Bearer [REDACTED]'];
        yield 'long token' => ['Bearer '.str_repeat('a', 40).'+secret', 'Bearer [REDACTED]'];
        yield 'lowercase scheme' => ['bearer demo+secret/token==', 'bearer [REDACTED]'];
        yield 'mixed case scheme' => ['bEaReR demo~secret', 'bEaReR [REDACTED]'];
        yield 'multiple spaces' => ['Bearer   demo+secret/token==', 'Bearer [REDACTED]'];
        yield 'surrounding text' => ['Header: Bearer demo+secret/token==, request failed.', 'Header: Bearer [REDACTED], request failed.'];
        yield 'long basic credential' => ['Basic '.str_repeat('a', 40).'==', 'Basic [REDACTED]'];
    }

    #[Test]
    #[DataProvider('provideUrlsWithQueryParameters')]
    public function itMasksSensitiveQueryParameters(string $input, string $expected): void
    {
        $this->assertSame($expected, Sanitizer::sanitizeParams($input));
        $this->assertSame($expected, Sanitizer::sanitizeUrl($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideUrlsWithQueryParameters(): iterable
    {
        yield 'short token' => ['https://example.test/?access_token=short-secret', 'https://example.test/?access_token=[REDACTED]'];
        yield 'repeated keys and public values' => ['https://example.test/?q=hello%20world&token=one&token=two&sort=a+b', 'https://example.test/?q=hello%20world&token=[REDACTED]&token=[REDACTED]&sort=a+b'];
        yield 'encoded key and value' => ['https://example.test/?%61ccess%5Ftoken=a%26b%3Dc', 'https://example.test/?%61ccess%5Ftoken=[REDACTED]'];
        yield 'case insensitive key' => ['https://example.test/?API_KEY=short', 'https://example.test/?API_KEY=[REDACTED]'];
        yield 'nested parameter' => ['https://example.test/?credentials%5Bpassword%5D=short', 'https://example.test/?credentials%5Bpassword%5D=[REDACTED]'];
        yield 'fragment preserved' => ['https://example.test/?token=short#section', 'https://example.test/?token=[REDACTED]#section'];
        yield 'empty secret' => ['https://example.test/?secret=&page=2', 'https://example.test/?secret=[REDACTED]&page=2'];
        yield 'padding' => ['https://example.test/?token=demo+secret/token==', 'https://example.test/?token=[REDACTED]'];
        yield 'relative URL' => ['/path?session_id=short&lang=en', '/path?session_id=[REDACTED]&lang=en'];
        yield 'public query unchanged' => ['https://example.test/?q=a%2Fb&page=1&page=2&empty=#section', 'https://example.test/?q=a%2Fb&page=1&page=2&empty=#section'];
    }

    #[Test]
    public function itMasksQueryParametersInDiagnosticText(): void
    {
        $this->assertSame(
            'Failed "https://example.test/?token=[REDACTED]"; retry /path?api_key=[REDACTED] later.',
            Sanitizer::sanitizeParams('Failed "https://example.test/?token=short"; retry /path?api_key=other later.')
        );
    }

    #[Test]
    public function itMasksUrlCredentialsAndQueryParametersTogether(): void
    {
        $this->assertSame(
            'https://[REDACTED]@example.test:8080/path?password=[REDACTED]&page=2#section',
            Sanitizer::sanitizeUrl('https://user:pass@example.test:8080/path?password=short&page=2#section')
        );
    }

    #[Test]
    public function itMasksQueryParametersEvenWhenTheUrlCannotBeParsed(): void
    {
        $this->assertSame(
            'https://example.test:invalid/?token=[REDACTED]',
            Sanitizer::sanitizeUrl('https://example.test:invalid/?token=short')
        );
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
