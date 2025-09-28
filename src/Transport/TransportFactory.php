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

namespace Playwright\Transport;

use Playwright\Configuration\PlaywrightConfig;
use Playwright\Exception\RuntimeException;
use Playwright\Node\NodeBinaryResolver;
use Playwright\Transport\JsonRpc\JsonRpcTransport;
use Playwright\Transport\JsonRpc\ProcessLauncher;
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
