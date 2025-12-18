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

namespace Playwright\Tests\Unit\Page\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Page\Options\SetInputFilesOptions;

#[CoversClass(SetInputFilesOptions::class)]
final class SetInputFilesOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new SetInputFilesOptions(noWaitAfter: true);
        $this->assertSame($options, SetInputFilesOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $options = SetInputFilesOptions::from(['noWaitAfter' => true, 'timeout' => 5000.0]);
        $this->assertTrue($options->noWaitAfter);
        $this->assertSame(5000.0, $options->timeout);
    }

    public function testItThrowsExceptionForInvalidInput(): void
    {
        $this->expectException(\TypeError::class);

        SetInputFilesOptions::from('invalid');
    }

    public function testToReturnArray(): void
    {
        $options = new SetInputFilesOptions(
            noWaitAfter: true,
            timeout: 5000.0
        );

        $expected = [
            'noWaitAfter' => true,
            'timeout' => 5000.0,
        ];

        $this->assertSame($expected, $options->toArray());
    }
}
