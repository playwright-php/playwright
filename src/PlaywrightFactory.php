<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP;

use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\Transport\TransportFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Simple factory for standard Playwright usage.
 *
 * **The recommended way to create PlaywrightClient instances.**
 *
 * ```php
 * // Basic usage
 * $playwright = PlaywrightFactory::create();
 *
 * // With custom configuration
 * $config = new PlaywrightConfig(headless: false, screenshotDir: '/screenshots');
 * $playwright = PlaywrightFactory::create($config, $logger);
 *
 * // Environment-based configuration
 * $config = PlaywrightConfigBuilder::fromEnv()->build();
 * $playwright = PlaywrightFactory::create($config);
 * ```
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class PlaywrightFactory
{
    public static function create(
        ?PlaywrightConfig $config = null,
        ?LoggerInterface $logger = null,
    ): PlaywrightClient {
        $config ??= new PlaywrightConfig();
        $logger ??= new NullLogger();

        $transportFactory = new TransportFactory();
        $transport = $transportFactory->create($config, $logger);

        return new PlaywrightClient($transport, $logger, $config);
    }
}
