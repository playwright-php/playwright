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
use Playwright\Page\Options\StyleTagOptions;

#[CoversClass(StyleTagOptions::class)]
final class StyleTagOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new StyleTagOptions(url: 'http://example.com/style.css');
        $this->assertSame($options, StyleTagOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $options = StyleTagOptions::from(['url' => 'http://example.com/style.css', 'content' => 'body { color: red; }']);
        $this->assertSame('http://example.com/style.css', $options->url);
        $this->assertSame('body { color: red; }', $options->content);
    }

    public function testItThrowsExceptionForInvalidInput(): void
    {
        $this->expectException(\TypeError::class);

        StyleTagOptions::from('invalid');
    }

    public function testToReturnArray(): void
    {
        $options = new StyleTagOptions(
            url: 'http://example.com/style.css',
            path: 'style.css',
            content: 'body { color: red; }'
        );

        $expected = [
            'url' => 'http://example.com/style.css',
            'path' => 'style.css',
            'content' => 'body { color: red; }',
        ];

        $this->assertSame($expected, $options->toArray());
    }
}
