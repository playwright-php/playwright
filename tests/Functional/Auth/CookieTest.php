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

namespace Playwright\Tests\Functional\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Browser\BrowserContext;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
#[CoversClass(BrowserContext::class)]
final class CookieTest extends FunctionalTestCase
{
    public function testCanSetCookie(): void
    {
        $this->goto('/cookies.html');

        $this->context->addCookies([
            [
                'name' => 'test_cookie',
                'value' => 'test_value',
                'url' => $this->getBaseUrl(),
            ],
        ]);

        $this->page->reload();

        $cookieValue = $this->page->evaluate('document.cookie');
        self::assertStringContainsString('test_cookie=test_value', $cookieValue);
    }

    public function testCanSetMultipleCookies(): void
    {
        $this->goto('/cookies.html');

        $this->context->addCookies([
            [
                'name' => 'cookie1',
                'value' => 'value1',
                'url' => $this->getBaseUrl(),
            ],
            [
                'name' => 'cookie2',
                'value' => 'value2',
                'url' => $this->getBaseUrl(),
            ],
            [
                'name' => 'cookie3',
                'value' => 'value3',
                'url' => $this->getBaseUrl(),
            ],
        ]);

        $this->page->reload();

        $cookies = $this->context->cookies();
        $cookieNames = \array_column($cookies, 'name');

        self::assertContains('cookie1', $cookieNames);
        self::assertContains('cookie2', $cookieNames);
        self::assertContains('cookie3', $cookieNames);
    }

    public function testCanGetCookies(): void
    {
        $this->goto('/cookies.html');

        $this->context->addCookies([
            [
                'name' => 'my_cookie',
                'value' => 'my_value',
                'url' => $this->getBaseUrl(),
            ],
        ]);

        $cookies = $this->context->cookies();

        $myCookie = null;
        foreach ($cookies as $cookie) {
            if ('my_cookie' === $cookie['name']) {
                $myCookie = $cookie;
                break;
            }
        }

        self::assertNotNull($myCookie);
        self::assertSame('my_value', $myCookie['value']);
    }

    public function testCanDeleteCookies(): void
    {
        $this->goto('/cookies.html');

        $this->context->addCookies([
            [
                'name' => 'to_delete',
                'value' => 'delete_me',
                'url' => $this->getBaseUrl(),
            ],
        ]);

        $cookies = $this->context->cookies();
        $cookieNames = \array_column($cookies, 'name');
        self::assertContains('to_delete', $cookieNames);

        $this->context->clearCookies();

        $cookiesAfter = $this->context->cookies();
        self::assertEmpty($cookiesAfter);
    }

    public function testCanSetCookieWithExpiration(): void
    {
        $this->goto('/cookies.html');

        $expires = \time() + 3600;

        $this->context->addCookies([
            [
                'name' => 'persistent_cookie',
                'value' => 'persistent_value',
                'url' => $this->getBaseUrl(),
                'expires' => $expires,
            ],
        ]);

        $cookies = $this->context->cookies();

        $persistentCookie = null;
        foreach ($cookies as $cookie) {
            if ('persistent_cookie' === $cookie['name']) {
                $persistentCookie = $cookie;
                break;
            }
        }

        self::assertNotNull($persistentCookie);
        self::assertSame('persistent_value', $persistentCookie['value']);
        self::assertArrayHasKey('expires', $persistentCookie);
    }

    public function testCookiesFromJavaScript(): void
    {
        $this->goto('/cookies.html');

        $this->page->click('#set-simple-cookie');

        $this->page->waitForSelector('#cookies-output');

        $cookies = $this->context->cookies();
        $cookieNames = \array_column($cookies, 'name');

        self::assertContains('simple_cookie', $cookieNames);
    }

    public function testCanClearAllCookies(): void
    {
        $this->goto('/cookies.html');

        $this->context->addCookies([
            ['name' => 'cookie1', 'value' => 'value1', 'url' => $this->getBaseUrl()],
            ['name' => 'cookie2', 'value' => 'value2', 'url' => $this->getBaseUrl()],
            ['name' => 'cookie3', 'value' => 'value3', 'url' => $this->getBaseUrl()],
        ]);

        $cookiesBefore = $this->context->cookies();
        self::assertNotEmpty($cookiesBefore);

        $this->context->clearCookies();

        $cookiesAfter = $this->context->cookies();
        self::assertEmpty($cookiesAfter);
    }
}
