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

namespace Playwright\Tests\Unit\Locator\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Locator\Options\TextContentOptions;

#[CoversClass(TextContentOptions::class)]
final class TextContentOptionsTest extends TestCase
{
    public function testToArrayIncludesAllProperties(): void
    {
        $options = TextContentOptions::from([
            'timeout' => 3500.0,
        ]);

        $result = $options->toArray();

        $this->assertSame(3500.0, $result['timeout']);
    }

    public function testToArrayExcludesNullProperties(): void
    {
        $options = TextContentOptions::from([]);

        $result = $options->toArray();

        $this->assertArrayNotHasKey('timeout', $result);
    }

    public function testFromAcceptsSelfInstance(): void
    {
        $original = new TextContentOptions(timeout: 2000.0);
        $result = TextContentOptions::from($original);

        $this->assertSame($original, $result);
    }
}
