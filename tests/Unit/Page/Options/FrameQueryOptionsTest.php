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
use Playwright\Page\Options\FrameQueryOptions;

#[CoversClass(FrameQueryOptions::class)]
final class FrameQueryOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new FrameQueryOptions(name: 'frame1');
        $this->assertSame($options, FrameQueryOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $options = FrameQueryOptions::from(['name' => 'frame1', 'url' => 'http://example.com']);
        $this->assertSame('frame1', $options->name);
        $this->assertSame('http://example.com', $options->url);
    }

    public function testItThrowsExceptionForInvalidInput(): void
    {
        $this->expectException(\TypeError::class);

        FrameQueryOptions::from('invalid');
    }

    public function testToReturnArray(): void
    {
        $options = new FrameQueryOptions(
            name: 'frame1',
            url: 'http://example.com',
            urlRegex: '.*example.com.*'
        );

        $expected = [
            'name' => 'frame1',
            'url' => 'http://example.com',
            'urlRegex' => '.*example.com.*',
        ];

        $this->assertSame($expected, $options->toArray());
    }
}
