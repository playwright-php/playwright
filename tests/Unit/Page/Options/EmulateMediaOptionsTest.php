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
use Playwright\Page\Options\EmulateMediaOptions;

#[CoversClass(EmulateMediaOptions::class)]
final class EmulateMediaOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new EmulateMediaOptions(media: 'print');

        $this->assertSame($options, EmulateMediaOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $options = EmulateMediaOptions::from([
            'colorScheme' => 'dark',
            'contrast' => 'more',
            'forcedColors' => 'active',
            'media' => 'print',
            'reducedMotion' => 'reduce',
        ]);

        $this->assertSame('dark', $options->colorScheme);
        $this->assertSame('more', $options->contrast);
        $this->assertSame('active', $options->forcedColors);
        $this->assertSame('print', $options->media);
        $this->assertSame('reduce', $options->reducedMotion);
    }

    public function testToReturnArray(): void
    {
        $options = new EmulateMediaOptions(
            colorScheme: 'light',
            contrast: 'no-preference',
            forcedColors: 'none',
            media: 'screen',
            reducedMotion: 'no-preference',
        );

        $this->assertSame([
            'colorScheme' => 'light',
            'contrast' => 'no-preference',
            'forcedColors' => 'none',
            'media' => 'screen',
            'reducedMotion' => 'no-preference',
        ], $options->toArray());
    }

    public function testItOmitsUnsetFeatures(): void
    {
        $options = new EmulateMediaOptions(media: 'no-override');

        $this->assertSame(['media' => 'no-override'], $options->toArray());
    }
}
