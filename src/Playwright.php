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

    /**
     * @param array<string, mixed> $options
     */
    public static function chromium(array $options = []): BrowserContextInterface
    {
        return self::launch('chromium', $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function firefox(array $options = []): BrowserContextInterface
    {
        return self::launch('firefox', $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function safari(array $options = []): BrowserContextInterface
    {
        return self::launch('webkit', $options);
    }

    /**
     * @param array<string, mixed> $options
     */
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
            $slowMo = $options['slowMo'];
            if (is_numeric($slowMo)) {
                $builder->withSlowMo((int) $slowMo);
            }
        }
        if (!empty($options['args'])) {
            $args = $options['args'];
            if (is_array($args)) {
                $stringArgs = array_filter($args, 'is_string');
                $builder->withArgs($stringArgs);
            }
        }

        $browser = $builder->launch();
        $contextOptions = $options['context'] ?? [];

        if (!is_array($contextOptions)) {
            $contextOptions = [];
        }

        /** @phpstan-var array<string, mixed> $contextOptions */
        $context = empty($contextOptions) ? $browser->context() : $browser->newContext($contextOptions);

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
