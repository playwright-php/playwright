<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Transport;

use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\Exception\RuntimeException;
use PlaywrightPHP\Node\NodeBinaryResolver;
use PlaywrightPHP\Transport\JsonRpc\JsonRpcTransport;
use PlaywrightPHP\Transport\JsonRpc\ProcessLauncher;
use Psr\Log\LoggerInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class TransportFactory
{
    public function create(PlaywrightConfig $config, LoggerInterface $logger): TransportInterface
    {
        $serverFinder = new ServerFinder();
        $serverScriptPath = $serverFinder->getServerScriptPath();

        if (!$serverScriptPath || !file_exists($serverScriptPath)) {
            throw new RuntimeException('playwright-server.js not found.');
        }

        $nodePath = $config->nodePath ?? (new NodeBinaryResolver())->resolve();
        $command = [$nodePath, $serverScriptPath];

        $processLauncher = new ProcessLauncher($logger);

        return new JsonRpcTransport(
            $processLauncher,
            [
                'command' => $command,
                'timeout' => $config->timeoutMs / 1000,
                'cwd' => dirname($serverScriptPath),
                'env' => $config->env,
            ],
            $logger
        );
    }
}
