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

namespace Playwright\Tests\Functional\Network;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Network\Request;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Request::class)]
final class RequestBodyTest extends FunctionalTestCase
{
    public function testPostDataBufferCarriesBinaryBodies(): void
    {
        $this->goto('/index.html');

        $binary = "\xff\xfe\x00\x41\x80";
        $captured = null;

        $this->page->route('**/upload', function ($route) use (&$captured) {
            $captured = $route->request()->postDataBuffer();
            $route->fulfill(['status' => 204, 'body' => '']);
        });

        $this->page->evaluate("async () => {
            const binary = new Uint8Array([0xff, 0xfe, 0x00, 0x41, 0x80]);
            await fetch('/upload', { method: 'POST', body: binary });
        }");

        self::assertSame($binary, $captured);
    }

    public function testPostDataBufferMatchesTextBodies(): void
    {
        $this->goto('/index.html');

        $captured = null;

        $this->page->route('**/upload', function ($route) use (&$captured) {
            $captured = $route->request()->postDataBuffer();
            $route->fulfill(['status' => 204, 'body' => '']);
        });

        $this->page->evaluate("async () => {
            await fetch('/upload', { method: 'POST', body: 'plain text body' });
        }");

        self::assertSame('plain text body', $captured);
    }
}
