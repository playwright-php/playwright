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

namespace Playwright\Tests\Unit\Selector\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Selector\Options\RegisterOptions;

#[CoversClass(RegisterOptions::class)]
final class RegisterOptionsTest extends TestCase
{
    public function testCanBeCreatedFromConstructor(): void
    {
        $options = new RegisterOptions(contentScript: true);

        $this->assertTrue($options->contentScript);
        $this->assertEquals(['contentScript' => true], $options->toArray());
    }

    public function testCanBeCreatedFromArray(): void
    {
        $options = RegisterOptions::from(['contentScript' => true]);

        $this->assertTrue($options->contentScript);
    }

    public function testCanBeCreatedFromSelf(): void
    {
        $original = new RegisterOptions(contentScript: true);
        $options = RegisterOptions::from($original);

        $this->assertSame($original, $options);
    }
}
