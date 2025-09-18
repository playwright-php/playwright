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

namespace Playwright\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\PlaywrightClient;
use Playwright\PlaywrightFactory;
use Psr\Log\NullLogger;

#[CoversClass(PlaywrightFactory::class)]
class PlaywrightFactoryTest extends TestCase
{
    #[Test]
    public function itCanCreateClientWithDefaults(): void
    {
        $client = PlaywrightFactory::create();

        $this->assertInstanceOf(PlaywrightClient::class, $client);
    }

    #[Test]
    public function itCanCreateClientWithCustomConfig(): void
    {
        $config = new PlaywrightConfig(
            nodePath: '/usr/bin/node',
            timeoutMs: 30000
        );

        $client = PlaywrightFactory::create($config);

        $this->assertInstanceOf(PlaywrightClient::class, $client);
    }

    #[Test]
    public function itCanCreateClientWithCustomLogger(): void
    {
        $logger = new NullLogger();
        $client = PlaywrightFactory::create(null, $logger);

        $this->assertInstanceOf(PlaywrightClient::class, $client);
    }

    #[Test]
    public function itCanCreateClientWithBothConfigAndLogger(): void
    {
        $config = new PlaywrightConfig(
            timeoutMs: 45000
        );
        $logger = new NullLogger();
        $client = PlaywrightFactory::create($config, $logger);

        $this->assertInstanceOf(PlaywrightClient::class, $client);
    }

    #[Test]
    public function itCreatesNewInstancesOnEachCall(): void
    {
        $client1 = PlaywrightFactory::create();
        $client2 = PlaywrightFactory::create();

        $this->assertNotSame($client1, $client2);
    }
}
