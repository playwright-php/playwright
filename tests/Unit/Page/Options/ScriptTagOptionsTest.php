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
use Playwright\Page\Options\ScriptTagOptions;

#[CoversClass(ScriptTagOptions::class)]
final class ScriptTagOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new ScriptTagOptions(url: 'http://example.com/script.js');
        $this->assertSame($options, ScriptTagOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $options = ScriptTagOptions::from(['url' => 'http://example.com/script.js', 'type' => 'module']);
        $this->assertSame('http://example.com/script.js', $options->url);
        $this->assertSame('module', $options->type);
    }

    public function testItThrowsExceptionForInvalidInput(): void
    {
        $this->expectException(\TypeError::class);

        ScriptTagOptions::from('invalid');
    }

    public function testToReturnArray(): void
    {
        $options = new ScriptTagOptions(
            url: 'http://example.com/script.js',
            path: 'script.js',
            content: 'console.log("hello")',
            type: 'module'
        );

        $expected = [
            'url' => 'http://example.com/script.js',
            'path' => 'script.js',
            'content' => 'console.log("hello")',
            'type' => 'module',
        ];

        $this->assertSame($expected, $options->toArray());
    }
}
