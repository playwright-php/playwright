<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\PlaywrightClient;
use PlaywrightPHP\PlaywrightFactory;
use Psr\Log\LoggerInterface;

#[CoversClass(PlaywrightFactory::class)]
final class PlaywrightFactoryTest extends TestCase
{
    public function testCreateWithDefaultConfig(): void
    {
        $factory = new PlaywrightFactory();

        $client = $factory->create();

        $this->assertInstanceOf(PlaywrightClient::class, $client);
    }

    public function testCreateWithCustomConfig(): void
    {
        $config = new PlaywrightConfig(
            nodePath: '/usr/bin/node',
            timeoutMs: 60000,
            headless: false,
            tracingEnabled: true
        );

        $factory = new PlaywrightFactory();

        $client = $factory->create($config);

        $this->assertInstanceOf(PlaywrightClient::class, $client);
    }

    public function testCreateWithLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $factory = new PlaywrightFactory();

        $client = $factory->create(null, $logger);

        $this->assertInstanceOf(PlaywrightClient::class, $client);
    }

    public function testCreateWithConfigAndLogger(): void
    {
        $config = new PlaywrightConfig(
            nodePath: '/opt/node/bin/node',
            timeoutMs: 45000,
            headless: true,
            tracingEnabled: false
        );

        $logger = $this->createMock(LoggerInterface::class);
        $factory = new PlaywrightFactory();

        $client = $factory->create($config, $logger);

        $this->assertInstanceOf(PlaywrightClient::class, $client);
    }
}
