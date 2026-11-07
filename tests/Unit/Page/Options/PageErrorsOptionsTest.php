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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Playwright\Exception\InvalidArgumentException;
use Playwright\Page\Options\PageErrorsOptions;

#[CoversClass(PageErrorsOptions::class)]
final class PageErrorsOptionsTest extends TestCase
{
    public function testItLeavesTheDefaultToPlaywright(): void
    {
        $options = PageErrorsOptions::from();

        $this->assertNull($options->filter);
        $this->assertSame([], $options->toArray());
        $this->assertSame([], PageErrorsOptions::from(['filter' => null])->toArray());
    }

    #[DataProvider('provideFilters')]
    public function testItAcceptsArraysAndObjects(string $filter): void
    {
        $options = new PageErrorsOptions(filter: $filter);

        $this->assertSame($options, PageErrorsOptions::from($options));
        $this->assertSame(['filter' => $filter], $options->toArray());
        $this->assertEquals($options, PageErrorsOptions::from(['filter' => $filter]));
    }

    public static function provideFilters(): iterable
    {
        yield ['all'];
        yield ['since-navigation'];
    }

    public function testItRejectsAnInvalidFilterInTheConstructor(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PageErrorsOptions(filter: 'invalid');
    }

    public function testItRejectsAnInvalidFilterInAnArray(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PageErrorsOptions::from(['filter' => 'invalid']);
    }

    #[DataProvider('provideInvalidTypes')]
    public function testItRejectsNonStringFilters(mixed $filter): void
    {
        $this->expectException(\TypeError::class);

        PageErrorsOptions::from(['filter' => $filter]);
    }

    public static function provideInvalidTypes(): iterable
    {
        yield 'integer' => [42];
        yield 'boolean' => [false];
        yield 'array' => [[]];
    }
}
