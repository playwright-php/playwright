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

namespace Playwright\Tests\Unit\FileChooser\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\FileChooser\Options\SetFilesOptions;

#[CoversClass(SetFilesOptions::class)]
final class SetFilesOptionsTest extends TestCase
{
    public function testCanBeCreatedFromConstructor(): void
    {
        $options = new SetFilesOptions(noWaitAfter: true, timeout: 1000.0);

        $this->assertTrue($options->noWaitAfter);
        $this->assertSame(1000.0, $options->timeout);
        $this->assertEquals(['noWaitAfter' => true, 'timeout' => 1000.0], $options->toArray());
    }

    public function testCanBeCreatedFromArray(): void
    {
        $options = SetFilesOptions::from(['noWaitAfter' => true, 'timeout' => 1000.0]);

        $this->assertTrue($options->noWaitAfter);
        $this->assertSame(1000.0, $options->timeout);
    }

    public function testCanBeCreatedFromSelf(): void
    {
        $original = new SetFilesOptions(timeout: 1000.0);
        $options = SetFilesOptions::from($original);

        $this->assertSame($original, $options);
    }
}
