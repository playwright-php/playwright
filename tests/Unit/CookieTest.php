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

namespace Playwright\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Cookie;

#[CoversClass(Cookie::class)]
final class CookieTest extends TestCase
{
    #[DataProvider('validCookieProvider')]
    #[Test]
    public function itCreatesFromArrayValidCookie(array $cookieData, int|float $expires): void
    {
        $cookie = Cookie::fromArray($cookieData);

        $this->assertSame('test', $cookie->name);
        $this->assertSame('value', $cookie->value);
        $this->assertSame('example.com', $cookie->domain);
        $this->assertSame('/', $cookie->path);
        $this->assertSame($expires, $cookie->expires);
        $this->assertTrue($cookie->httpOnly);
        $this->assertTrue($cookie->secure);
        $this->assertSame('Strict', $cookie->sameSite);
    }

    public static function validCookieProvider(): iterable
    {
        yield 'expires with int' => [
            [
                'name' => 'test',
                'value' => 'value',
                'domain' => 'example.com',
                'path' => '/',
                'expires' => 3600,
                'httpOnly' => true,
                'secure' => true,
                'sameSite' => 'Strict',
            ],
            3600,
        ];

        yield 'expires with float' => [
            [
                'name' => 'test',
                'value' => 'value',
                'domain' => 'example.com',
                'path' => '/',
                'expires' => 3600.5,
                'httpOnly' => true,
                'secure' => true,
                'sameSite' => 'Strict',
            ],
            3600.5,
        ];
    }

    #[DataProvider('invalidCookieProvider')]
    #[Test]
    public function itFailsToCreateFromArrayInvalidCookie(array $cookieData): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Cookie::fromArray($cookieData);
    }

    public static function invalidCookieProvider(): iterable
    {
        yield 'Invalid cookie data structure' => [['invalid' => 'data']];
        yield 'Invalid cookie expires as random string' => [['name' => 'test', 'value' => 'value', 'domain' => 'example.com', 'path' => '/', 'expires' => 'invalid', 'httpOnly' => true, 'secure' => true, 'sameSite' => 'Strict']];
        yield 'Invalid cookie expires as string' => [['name' => 'test', 'value' => 'value', 'domain' => 'example.com', 'path' => '/', 'expires' => '123', 'httpOnly' => true, 'secure' => true, 'sameSite' => 'Strict']];
        yield 'Invalid cookie sameSite' => [['name' => 'test', 'value' => 'value', 'domain' => 'example.com', 'path' => '/', 'expires' => 123, 'httpOnly' => true, 'secure' => true, 'sameSite' => 'Random']];
    }

    #[Test]
    public function itReturnsValidCookieArray(): void
    {
        $cookie = Cookie::fromArray([
            'name' => 'test',
            'value' => 'value',
            'domain' => 'example.com',
            'path' => '/',
            'expires' => 3600,
            'httpOnly' => true,
            'secure' => true,
            'sameSite' => 'Strict',
        ]);

        $this->assertSame(
            [
                'name' => 'test',
                'value' => 'value',
                'domain' => 'example.com',
                'path' => '/',
                'expires' => 3600,
                'httpOnly' => true,
                'secure' => true,
                'sameSite' => 'Strict',
            ],
            $cookie->toArray()
        );
    }
}
