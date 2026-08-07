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

use Playwright\Browser\BrowserContextInterface;
use Playwright\Selector\SelectorsInterface;

final class Playwright
{
    private static ?PlaywrightClient $client = null;

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
    public static function webkit(array $options = []): BrowserContextInterface
    {
        return self::launch('webkit', $options);
    }

    /**
     * Alias for WebKit to match common browser naming.
     *
     * @param array<string, mixed> $options
     */
    public static function safari(array $options = []): BrowserContextInterface
    {
        return self::webkit($options);
    }

    /**
     * Selector engine registry shared by every browser this class launches.
     *
     * Engines must be registered before the pages that use them are created.
     */
    public static function selectors(): SelectorsInterface
    {
        return self::client()->selectors();
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function launch(string $browserType, array $options): BrowserContextInterface
    {
        $client = self::client();

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

        $typedOptions = [];
        foreach ($contextOptions as $key => $value) {
            if (is_string($key)) {
                $typedOptions[$key] = $value;
            }
        }

        return empty($typedOptions) ? $browser->context() : $browser->newContext($typedOptions);
    }

    private static function client(): PlaywrightClient
    {
        $client = self::$client;
        if (null === $client) {
            $client = self::$client = PlaywrightFactory::create();
            register_shutdown_function(static function () use ($client): void {
                try {
                    $client->close();
                } catch (\Throwable) {
                }
            });
        }

        return $client;
    }
}
