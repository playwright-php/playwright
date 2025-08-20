<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Transport;

use PlaywrightPHP\Exception\NetworkException;
use PlaywrightPHP\Node\NodeBinaryResolver;
use PlaywrightPHP\Node\NodeBinaryResolverInterface;

/**
 * Finds Playwright server installation and configuration.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ServerFinder
{
    public function __construct(
        private readonly ?NodeBinaryResolverInterface $nodeResolver = null,
    ) {
    }

    public function findPlaywright(): ?string
    {
        $possiblePaths = [
            // Current project
            getcwd().'/node_modules/playwright',
            // Parent directories (monorepo)
            dirname(getcwd()).'/node_modules/playwright',
            dirname(dirname(getcwd())).'/node_modules/playwright',
            // Custom user path
            $this->getUserConfiguredPath(),
        ];

        foreach ($possiblePaths as $path) {
            if (is_dir($path)) {
                return realpath($path);
            }
        }

        return null;
    }

    private function getUserConfiguredPath(): ?string
    {
        // From environment variable
        if ($envPath = getenv('PLAYWRIGHT_PATH')) {
            return $envPath;
        }

        // From composer.json extra config
        $composerPath = getcwd().'/composer.json';
        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);

            return $composer['extra']['playwright-php']['playwright-path'] ?? null;
        }

        return null;
    }

    /**
     * Find server configuration including Node.js binary and Playwright installation.
     *
     * @return array{strategy: string, command: list<string>, env: array<string, string>}
     */
    public function findServer(): array
    {
        $playwrightPath = $this->findPlaywright();
        if (!$playwrightPath) {
            throw new NetworkException('Playwright not found. Please run: npm install playwright');
        }

        // Use NodeBinaryResolver to find Node.js
        $nodeResolver = $this->nodeResolver ?? new NodeBinaryResolver();
        $nodePath = $nodeResolver->resolve();

        $serverScript = (new ServerManager())->getServerScriptPath();

        return [
            'strategy' => 'project',
            'command' => [$nodePath, $serverScript],
            'env' => ['PLAYWRIGHT_PATH' => $playwrightPath],
        ];
    }
}
