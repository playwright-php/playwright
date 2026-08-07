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

namespace Playwright\Tests\Unit\WebError;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Page\PageInterface;
use Playwright\WebError\WebError;

#[CoversClass(WebError::class)]
final class WebErrorTest extends TestCase
{
    public function testErrorAndPage(): void
    {
        $error = new \RuntimeException('boom');
        $page = $this->createMock(PageInterface::class);

        $webError = new WebError($error, $page);

        $this->assertSame($error, $webError->error());
        $this->assertSame($page, $webError->page());
    }

    public function testLocationReturnsNullWhenUnknown(): void
    {
        $webError = new WebError(new \RuntimeException('boom'));

        $this->assertNull($webError->page());
        $this->assertNull($webError->location());
    }

    public function testLocation(): void
    {
        $webError = new WebError(
            new \RuntimeException('boom'),
            null,
            ['url' => 'https://example.com/app.js', 'line' => 12, 'column' => 4],
        );

        $this->assertSame(
            ['url' => 'https://example.com/app.js', 'line' => 12, 'column' => 4],
            $webError->location(),
        );
    }
}
