<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Transport;

use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\Node\NodeBinaryResolver;
use Psr\Log\LoggerInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class TransportFactory
{
    public function create(PlaywrightConfig $config, LoggerInterface $logger): TransportInterface
    {
        $serverManager = new ServerManager();
        $serverScriptPath = $serverManager->getServerScriptPath();

        if (!$serverScriptPath || !file_exists($serverScriptPath)) {
            throw new \RuntimeException('playwright-server.js not found.');
        }

        $nodePath = $config->nodePath ?? (new NodeBinaryResolver())->resolve();
        $command = [$nodePath, $serverScriptPath];

        $transportConfig = [
            'command' => $command,
            'timeout' => $config->timeoutMs,
            'cwd' => dirname($serverScriptPath), // Removed legacy cwd override
            'env' => $config->env,
            'verbose' => false, // TODO: Integrate with logger instead of verbose flag
        ];

        return new ProcessTransport($transportConfig, $logger);
    }
}
