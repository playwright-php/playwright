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
use Playwright\Browser\StorageState;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(BrowserContext::class)]
#[CoversClass(StorageState::class)]
final class StorageStateTest extends FunctionalTestCase
{
    public function testCanSetStorageStateOnExistingContext(): void
    {
        $storageState = StorageState::fromArray([
            'cookies' => [[
                'name' => 'auth_token',
                'value' => 'secret-123',
                'domain' => '127.0.0.1',
                'path' => '/',
                'expires' => -1,
                'httpOnly' => false,
                'secure' => false,
                'sameSite' => 'Lax',
            ]],
            'origins' => [[
                'origin' => $this->getBaseUrl(),
                'localStorage' => [[
                    'name' => 'user',
                    'value' => 'alice',
                ]],
            ]],
        ]);

        $this->context->setStorageState($storageState);

        $this->goto('/index.html');

        $cookieNames = array_column($this->context->cookies(), 'name');
        $this->assertContains('auth_token', $cookieNames);

        $this->assertSame('alice', $this->page->evaluate("() => localStorage.getItem('user')"));
    }

    public function testCanReloadASavedStorageState(): void
    {
        $this->goto('/index.html');
        $this->page->evaluate("() => localStorage.setItem('session', 'saved-value')");
        $this->context->addCookies([[
            'name' => 'session_cookie',
            'value' => 'cookie-value',
            'domain' => '127.0.0.1',
            'path' => '/',
        ]]);

        $file = sys_get_temp_dir().'/pw-php-storage-state-'.uniqid().'.json';

        try {
            $this->context->saveStorageState($file);

            $fresh = $this->browser->newContext();

            try {
                $fresh->loadStorageState($file);

                $page = $fresh->newPage();
                $page->goto($this->getBaseUrl().'/index.html');

                $cookieNames = array_column($fresh->cookies(), 'name');
                $this->assertContains('session_cookie', $cookieNames);

                $this->assertSame('saved-value', $page->evaluate("() => localStorage.getItem('session')"));
            } finally {
                $fresh->close();
            }
        } finally {
            @unlink($file);
        }
    }
}
