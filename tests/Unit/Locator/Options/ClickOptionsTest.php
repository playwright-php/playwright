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

namespace Playwright\Tests\Unit\Locator\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Input\ModifierKey;
use Playwright\Locator\Options\ClickOptions;

#[CoversClass(ClickOptions::class)]
final class ClickOptionsTest extends TestCase
{
    public function testCanBeCreatedFromConstructor(): void
    {
        $options = new ClickOptions(
            button: 'right',
            clickCount: 2,
            delay: 100.0,
            position: ['x' => 10, 'y' => 20],
            modifiers: [ModifierKey::Alt],
            force: true,
            noWaitAfter: true,
            timeout: 5000.0,
            trial: true,
        );

        $this->assertEquals('right', $options->button);
        $this->assertEquals(2, $options->clickCount);
        $this->assertEquals(100.0, $options->delay);
        $this->assertEquals(['x' => 10, 'y' => 20], $options->position);
        $this->assertEquals([ModifierKey::Alt], $options->modifiers);
        $this->assertTrue($options->force);
        $this->assertTrue($options->noWaitAfter);
        $this->assertEquals(5000.0, $options->timeout);
        $this->assertTrue($options->trial);

        $this->assertEquals([
            'button' => 'right',
            'clickCount' => 2,
            'delay' => 100.0,
            'position' => ['x' => 10, 'y' => 20],
            'modifiers' => [ModifierKey::Alt],
            'force' => true,
            'noWaitAfter' => true,
            'timeout' => 5000.0,
            'trial' => true,
        ], $options->toArray());
    }

    public function testCanBeCreatedFromArray(): void
    {
        $options = ClickOptions::from([
            'button' => 'middle',
            'clickCount' => 3,
            'delay' => 200.0,
            'position' => ['x' => 30, 'y' => 40],
            'modifiers' => [ModifierKey::Control],
            'force' => false,
            'noWaitAfter' => false,
            'timeout' => 1000.0,
            'trial' => false,
        ]);

        $this->assertEquals('middle', $options->button);
        $this->assertEquals(3, $options->clickCount);
        $this->assertEquals(200.0, $options->delay);
        $this->assertEquals(['x' => 30, 'y' => 40], $options->position);
        $this->assertEquals([ModifierKey::Control], $options->modifiers);
        $this->assertFalse($options->force);
        $this->assertFalse($options->noWaitAfter);
        $this->assertEquals(1000.0, $options->timeout);
        $this->assertFalse($options->trial);
    }

    public function testCanBeCreatedFromSelf(): void
    {
        $original = new ClickOptions(button: 'left');
        $options = ClickOptions::from($original);

        $this->assertSame($original, $options);
    }
}
