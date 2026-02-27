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

namespace Playwright;

use Playwright\Configuration\PlaywrightConfig;
use Playwright\Transport\TransportFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Simple factory for standard Playwright usage.
 *
 * **The recommended way to create PlaywrightClient instances.**
 *
 * ```php
 *
 * $playwright = PlaywrightFactory::create();
 *
 *
 * $config = new PlaywrightConfig(headless: false, screenshotDir: '/screenshots');
 * $playwright = PlaywrightFactory::create($config, $logger);
 *
 *
 * $config = PlaywrightConfigBuilder::fromEnv()->build();
 * $playwright = PlaywrightFactory::create($config);
 * ```
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class PlaywrightFactory
{
    public static function create(
        PlaywrightConfig $config = new PlaywrightConfig(),
        LoggerInterface $logger = new NullLogger(),
    ): PlaywrightClient {
        return new PlaywrightClient((new TransportFactory())->create($config, $logger), $logger, $config);
    }
}
