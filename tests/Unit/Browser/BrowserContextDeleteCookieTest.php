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

namespace Playwright\Tests\Unit\Browser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserContext;
use Playwright\Transport\TransportInterface;

#[CoversClass(BrowserContext::class)]
final class BrowserContextDeleteCookieTest extends TestCase
{
    public function testDeleteCookieSendsAddCookiesWithExpiry(): void
    {
        $transport = $this->createMock(TransportInterface::class);

        $calledAdd = false;
        $that = $this;
        $transport->method('send')->willReturnCallback(function (array $payload) use (&$calledAdd, $that) {
            if (($payload['action'] ?? null) === 'context.cookies') {
                return [
                    'cookies' => [
                        [
                            'name' => 'foo',
                            'value' => '123',
                            'domain' => 'example.com',
                            'path' => '/',
                            'expires' => time() + 3600,
                            'httpOnly' => false,
                            'secure' => false,
                            'sameSite' => 'Lax',
                        ],
                        [
                            'name' => 'bar',
                            'value' => 'x',
                            'domain' => 'example.com',
                            'path' => '/',
                            'expires' => time() + 3600,
                            'httpOnly' => false,
                            'secure' => false,
                            'sameSite' => 'Lax',
                        ],
                    ],
                ];
            }

            if (($payload['action'] ?? null) === 'context.addCookies') {
                $that->assertArrayHasKey('cookies', $payload);
                $that->assertIsArray($payload['cookies']);
                $that->assertSame('foo', $payload['cookies'][0]['name']);
                $that->assertSame('example.com', $payload['cookies'][0]['domain']);
                $that->assertSame('/', $payload['cookies'][0]['path']);
                $that->assertSame(0, $payload['cookies'][0]['expires']);
                $calledAdd = true;

                return [];
            }

            return [];
        });

        $context = new BrowserContext($transport, 'ctx');
        $context->deleteCookie('foo');

        $this->assertTrue($calledAdd, 'context.addCookies should be called to expire matching cookies');
    }
}
