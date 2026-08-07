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

namespace Playwright\Tests\Unit\Assertions\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Assertions\Internal\AriaSnapshot;

#[CoversClass(AriaSnapshot::class)]
final class AriaSnapshotTest extends TestCase
{
    public function testNormalizeDropsBlankLinesAndTrailingWhitespace(): void
    {
        $this->assertSame(
            "- list:\n  - listitem: One",
            AriaSnapshot::normalize("\n- list:   \n\n  - listitem: One\n\n")
        );
    }

    public function testNormalizeStripsTheIndentationSharedByEveryLine(): void
    {
        $this->assertSame(
            "- list:\n  - listitem: One",
            AriaSnapshot::normalize("      - list:\n        - listitem: One")
        );
    }

    public function testNormalizeKeepsIndentationThatIsNotShared(): void
    {
        $this->assertSame(
            "- list:\n  - listitem: One\n- heading \"Two\"",
            AriaSnapshot::normalize("- list:\n  - listitem: One\n- heading \"Two\"")
        );
    }

    public function testNormalizeAcceptsWindowsAndClassicMacLineEndings(): void
    {
        $this->assertSame("- list:\n  - listitem: One", AriaSnapshot::normalize("- list:\r\n  - listitem: One"));
        $this->assertSame("- list:\n  - listitem: One", AriaSnapshot::normalize("- list:\r  - listitem: One"));
    }

    public function testNormalizeReturnsAnEmptyStringForABlankSnapshot(): void
    {
        $this->assertSame('', AriaSnapshot::normalize("  \n\n"));
    }
}
