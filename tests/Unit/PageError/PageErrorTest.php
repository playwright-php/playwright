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

namespace Playwright\Tests\Unit\PageError;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\PageError\PageError;
use Playwright\PageError\PageErrorInterface;

#[CoversClass(PageError::class)]
final class PageErrorTest extends TestCase
{
    public function testItPreservesJavaScriptErrorDetails(): void
    {
        $stack = "TypeError: boom\n    at https://example.test/app.js:12:3";
        $error = new PageError('TypeError', 'boom', $stack);

        $this->assertInstanceOf(PageErrorInterface::class, $error);
        $this->assertSame('TypeError', $error->name());
        $this->assertSame('boom', $error->message());
        $this->assertSame($stack, $error->stack());
    }

    public function testTheStackCanBeUnavailable(): void
    {
        $error = new PageError('Error', 'boom');

        $this->assertNull($error->stack());
    }
}
