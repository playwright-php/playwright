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

namespace Playwright\Tests\Unit\Tracing\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Regex;
use Playwright\Tracing\Options\StartHarOptions;

#[CoversClass(StartHarOptions::class)]
final class StartHarOptionsTest extends TestCase
{
    public function testEmptyOptionsProduceAnEmptyArray(): void
    {
        $this->assertSame([], (new StartHarOptions())->toArray());
    }

    public function testAllScalarOptionsAreForwarded(): void
    {
        $options = new StartHarOptions(
            content: 'attach',
            mode: 'minimal',
            resourcesDir: '/tmp/resources',
            urlFilter: '**/api/**',
        );

        $this->assertSame([
            'content' => 'attach',
            'mode' => 'minimal',
            'resourcesDir' => '/tmp/resources',
            'urlFilter' => '**/api/**',
        ], $options->toArray());
    }

    public function testRegexUrlFilterUsesADistinctKey(): void
    {
        $options = new StartHarOptions(urlFilter: new Regex('/\\/api\\/.*/i'));

        $this->assertSame(['urlFilterRegex' => '/\\/api\\/.*/i'], $options->toArray());
    }

    public function testFromArrayReadsEveryOption(): void
    {
        $options = StartHarOptions::from([
            'content' => 'omit',
            'mode' => 'full',
            'resourcesDir' => '/tmp/res',
            'urlFilter' => '**/*.png',
        ]);

        $this->assertSame('omit', $options->content);
        $this->assertSame('full', $options->mode);
        $this->assertSame('/tmp/res', $options->resourcesDir);
        $this->assertSame('**/*.png', $options->urlFilter);
    }

    public function testFromReturnsTheSameInstance(): void
    {
        $options = new StartHarOptions(mode: 'minimal');

        $this->assertSame($options, StartHarOptions::from($options));
    }
}
