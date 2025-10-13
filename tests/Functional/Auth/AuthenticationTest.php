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
final class AuthenticationTest extends FunctionalTestCase
{
    public function testCanLoginWithCredentials(): void
    {
        $this->goto('/auth.html');

        $this->page->locator('#username')->fill('testuser');
        $this->page->locator('#password')->fill('password123');
        $this->page->click('#login-btn');

        $this->page->waitForSelector('#auth-status:has-text("Authenticated")');

        $status = $this->page->locator('#auth-status')->textContent();
        self::assertSame('Authenticated', $status);
    }

    public function testCanLogout(): void
    {
        $this->goto('/auth.html');

        $this->page->locator('#username')->fill('testuser');
        $this->page->locator('#password')->fill('password123');
        $this->page->click('#login-btn');

        $this->page->waitForSelector('#logout-btn');
        $this->page->click('#logout-btn');

        $status = $this->page->locator('#auth-status')->textContent();
        self::assertSame('Not authenticated', $status);
    }

    public function testUserInfoDisplayedAfterLogin(): void
    {
        $this->goto('/auth.html');

        $this->page->locator('#username')->fill('john_doe');
        $this->page->locator('#password')->fill('secret');
        $this->page->click('#login-btn');

        $this->page->waitForSelector('#user-info');

        $userInfo = $this->page->locator('#user-info')->textContent();
        self::assertStringContainsString('john_doe', $userInfo);
    }

    public function testAuthPersistsAcrossPageReload(): void
    {
        $this->goto('/auth.html');

        $this->page->locator('#username')->fill('testuser');
        $this->page->locator('#password')->fill('password123');
        $this->page->click('#login-btn');

        $this->page->waitForSelector('#auth-status:has-text("Authenticated")');

        $this->page->reload();

        $this->page->waitForSelector('#auth-status:has-text("Authenticated")');

        $status = $this->page->locator('#auth-status')->textContent();
        self::assertSame('Authenticated', $status);
    }

    public function testCanGetAuthCookies(): void
    {
        $this->goto('/auth.html');

        $this->page->locator('#username')->fill('testuser');
        $this->page->locator('#password')->fill('password123');
        $this->page->click('#login-btn');

        $this->page->waitForSelector('#auth-status:has-text("Authenticated")');

        $cookies = $this->context->cookies();

        $cookieNames = \array_column($cookies, 'name');
        self::assertContains('auth_token', $cookieNames);
        self::assertContains('username', $cookieNames);
    }
}
