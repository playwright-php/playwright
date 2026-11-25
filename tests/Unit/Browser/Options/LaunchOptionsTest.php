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

namespace Playwright\Tests\Unit\Browser\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\Options\LaunchOptions;

#[CoversClass(LaunchOptions::class)]
final class LaunchOptionsTest extends TestCase
{
    public function testCanBeCreatedFromConstructor(): void
    {
        $options = new LaunchOptions(
            headless: false,
            slowMo: 50,
            channel: 'chrome',
            executablePath: '/usr/bin/chromium',
            proxy: ['server' => 'http://proxy.local:8080'],
            downloadsPath: '/tmp/downloads',
            args: ['--mute-audio'],
        );

        $this->assertEquals([
            'headless' => false,
            'slowMo' => 50,
            'channel' => 'chrome',
            'executablePath' => '/usr/bin/chromium',
            'proxy' => ['server' => 'http://proxy.local:8080'],
            'downloadsPath' => '/tmp/downloads',
            'args' => ['--mute-audio'],
        ], $options->toArray());
    }

    public function testOmitsUnsetOptions(): void
    {
        $this->assertSame([], (new LaunchOptions())->toArray());
        $this->assertSame(['channel' => 'msedge'], (new LaunchOptions(channel: 'msedge'))->toArray());
    }

    public function testCanBeCreatedFromArray(): void
    {
        $options = LaunchOptions::from([
            'headless' => true,
            'slowMo' => 10,
            'channel' => 'chromium',
            'executablePath' => '/opt/browser',
            'proxy' => ['server' => 'socks5://proxy.local:1080'],
            'downloadsPath' => '/tmp/dl',
            'args' => ['--lang=en'],
        ]);

        $this->assertTrue($options->headless);
        $this->assertSame(10, $options->slowMo);
        $this->assertSame('chromium', $options->channel);
        $this->assertSame('/opt/browser', $options->executablePath);
        $this->assertSame(['server' => 'socks5://proxy.local:1080'], $options->proxy);
        $this->assertSame('/tmp/dl', $options->downloadsPath);
        $this->assertSame(['--lang=en'], $options->args);
    }

    public function testCanBeCreatedFromSelf(): void
    {
        $original = new LaunchOptions(headless: true);

        $this->assertSame($original, LaunchOptions::from($original));
    }
}
