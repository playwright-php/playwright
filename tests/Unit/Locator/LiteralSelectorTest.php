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

namespace Playwright\Tests\Unit\Locator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Locator\LiteralSelector;

#[CoversClass(LiteralSelector::class)]
final class LiteralSelectorTest extends TestCase
{
    public function testNullBytesAreRejectedInsteadOfSelectingReplacementCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LiteralSelector::css("value\0suffix");
    }
}
