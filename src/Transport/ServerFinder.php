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

use Playwright\Exception\NetworkException;
use Playwright\Node\NodeBinaryResolver;
use Playwright\Node\NodeBinaryResolverInterface;

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
        $cwd = getcwd();
        if (false === $cwd) {
            return null;
        }

        $possiblePaths = [
            $cwd.'/node_modules/playwright',

            dirname($cwd).'/node_modules/playwright',
            dirname($cwd, 2).'/node_modules/playwright',

            $this->getUserConfiguredPath(),
        ];

        foreach ($possiblePaths as $path) {
            if (null !== $path && is_dir($path)) {
                $realPath = realpath($path);

                return false !== $realPath ? $realPath : null;
            }
        }

        return null;
    }

    /**
     * Get the path to the Playwright server script.
     */
    public function getServerScriptPath(): string
    {
        return __DIR__.'/../../bin/playwright-server.js';
    }

    private function getUserConfiguredPath(): ?string
    {
        if ($envPath = getenv('PLAYWRIGHT_PATH')) {
            return $envPath;
        }

        $composerPath = getcwd().'/composer.json';
        if (file_exists($composerPath)) {
            $contents = file_get_contents($composerPath);
            if (false === $contents) {
                return null;
            }
            $composer = json_decode($contents, true);
            if (!is_array($composer)) {
                return null;
            }

            $extra = $composer['extra'] ?? null;
            if (!is_array($extra)) {
                return null;
            }

            $Playwright = $extra['playwright-php'] ?? null;
            if (!is_array($Playwright)) {
                return null;
            }

            $playwrightPath = $Playwright['playwright-path'] ?? null;

            return is_string($playwrightPath) ? $playwrightPath : null;
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

        $nodeResolver = $this->nodeResolver ?? new NodeBinaryResolver();
        $nodePath = $nodeResolver->resolve();

        $serverScript = $this->getServerScriptPath();

        return [
            'strategy' => 'project',
            'command' => [$nodePath, $serverScript],
            'env' => ['PLAYWRIGHT_PATH' => $playwrightPath],
        ];
    }
}
