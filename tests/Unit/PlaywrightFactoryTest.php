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
use PHPUnit\Framework\TestCase;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\PlaywrightClient;
use Playwright\PlaywrightFactory;
use Psr\Log\LoggerInterface;

/**
 * Every test here must pass an explicit nodePath.
 *
 * TransportFactory only calls NodeBinaryResolver when the config leaves nodePath
 * null, and resolving spawns "node --version" against the real machine. A unit
 * test doing that depends on the environment and fails whenever the binary is
 * slow to answer. The environment-dependent cases live in
 * tests/Integration/PlaywrightFactoryTest.php, where CI installs Node.
 */
#[CoversClass(PlaywrightFactory::class)]
final class PlaywrightFactoryTest extends TestCase
{
    public function testCreateWithCustomConfig(): void
    {
        $config = new PlaywrightConfig(
            nodePath: '/usr/bin/node',
            headless: false,
            timeoutMs: 60000,
            tracingEnabled: true
        );

        $client = PlaywrightFactory::create($config);

        $this->assertInstanceOf(PlaywrightClient::class, $client);
    }

    public function testCreateWithConfigAndLogger(): void
    {
        $config = new PlaywrightConfig(
            nodePath: '/opt/node/bin/node',
            headless: true,
            timeoutMs: 45000,
            tracingEnabled: false
        );

        $logger = $this->createMock(LoggerInterface::class);

        $client = PlaywrightFactory::create($config, $logger);

        $this->assertInstanceOf(PlaywrightClient::class, $client);
    }
}
