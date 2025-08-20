<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP;

use PlaywrightPHP\Browser\BrowserContextInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class Playwright
{
    /** @var PlaywrightClient[] */
    private static array $clients = [];
    private static bool $shutdownRegistered = false;

    public static function chromium(array $options = []): BrowserContextInterface
    {
        return self::launch('chromium', $options);
    }

    public static function firefox(array $options = []): BrowserContextInterface
    {
        return self::launch('firefox', $options);
    }

    public static function safari(array $options = []): BrowserContextInterface
    {
        // Safari maps to WebKit
        return self::launch('webkit', $options);
    }

    private static function launch(string $browserType, array $options): BrowserContextInterface
    {
        $client = PlaywrightFactory::create();

        $builder = match ($browserType) {
            'chromium' => $client->chromium(),
            'firefox' => $client->firefox(),
            'webkit' => $client->webkit(),
            default => $client->chromium(),
        };

        if (array_key_exists('headless', $options)) {
            $builder->withHeadless((bool) $options['headless']);
        }
        if (array_key_exists('slowMo', $options)) {
            $builder->withSlowMo((int) $options['slowMo']);
        }
        if (!empty($options['args'])) {
            $builder->withArgs((array) $options['args']);
        }

        $browser = $builder->launch();
        $contextOptions = $options['context'] ?? [];
        $context = empty($contextOptions) ? $browser->context() : $browser->newContext($contextOptions);

        // Keep client alive and ensure graceful shutdown
        self::$clients[] = $client;
        self::registerShutdown();

        return $context;
    }

    private static function registerShutdown(): void
    {
        if (self::$shutdownRegistered) {
            return;
        }
        self::$shutdownRegistered = true;
        register_shutdown_function(static function (): void {
            foreach (self::$clients as $i => $client) {
                try {
                    $client->close();
                } catch (\Throwable) {
                }
                unset(self::$clients[$i]);
            }
        });
    }
}
