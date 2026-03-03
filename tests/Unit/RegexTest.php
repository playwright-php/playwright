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

namespace Playwright\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Regex;

#[CoversClass(Regex::class)]
final class RegexTest extends TestCase
{
    #[Test]
    public function itAcceptsValidPattern(): void
    {
        $regex = new Regex('/hello/i');
        $this->assertSame('/hello/i', $regex->pattern);
    }

    #[Test]
    public function itAcceptsPatternWithoutFlags(): void
    {
        $regex = new Regex('/hello/');
        $this->assertSame('/hello/', $regex->pattern);
    }

    #[Test]
    public function itAcceptsPatternWithMultipleFlags(): void
    {
        $regex = new Regex('/hello/gim');
        $this->assertSame('/hello/gim', $regex->pattern);
    }

    #[Test]
    public function itRejectsPatternWithoutOpeningDelimiter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must start with a "/" delimiter');
        new Regex('hello');
    }

    #[Test]
    public function itRejectsEmptyPattern(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Regex('');
    }

    #[Test]
    public function itRejectsPatternWithoutClosingDelimiter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('closing "/" delimiter');
        new Regex('/hello');
    }

    #[Test]
    public function itRejectsInvalidFlag(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regex flag "z"');
        new Regex('/hello/z');
    }

    #[Test]
    public function itRejectsInvalidRegexSyntax(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regex pattern');
        new Regex('/(/');
    }

    #[Test]
    public function itAcceptsJsOnlyFlags(): void
    {
        $regex = new Regex('/hello/gy');
        $this->assertSame('/hello/gy', $regex->pattern);
    }
}
