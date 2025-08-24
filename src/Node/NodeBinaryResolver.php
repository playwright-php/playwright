<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Node;

use PlaywrightPHP\Node\Exception\NodeBinaryNotFoundException;
use PlaywrightPHP\Node\Exception\NodeVersionTooLowException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Resolves Node.js binary path with version validation and cross-platform support.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class NodeBinaryResolver implements NodeBinaryResolverInterface
{
    private const string DEFAULT_MIN_VERSION = '18.0.0';

    private ?string $cached = null;

    /**
     * @param array<int, string> $additionalSearchPaths extra directories or binary paths to probe after PATH lookup
     */
    public function __construct(
        private readonly ?string $explicitPath = null,
        private readonly string $minVersion = self::DEFAULT_MIN_VERSION,
        private readonly ?LoggerInterface $logger = null,
        private readonly array $additionalSearchPaths = [],
        private readonly bool $strictExplicitPath = false,
    ) {
    }

    /**
     * Resolve the absolute Node.js binary path and verify version.
     *
     * @throws NodeBinaryNotFoundException
     * @throws NodeVersionTooLowException
     */
    public function resolve(): string
    {
        if (null !== $this->cached) {
            return $this->cached;
        }

        $candidates = [];

        if (null !== $this->explicitPath && '' !== $this->explicitPath) {
            $candidates[] = $this->explicitPath;

            if ($this->strictExplicitPath) {
                $node = $this->normalizeIfDirectory($this->explicitPath);
                if (!$this->isExecutable($node)) {
                    throw new NodeBinaryNotFoundException(sprintf('Explicit Node.js binary not found or not executable: %s', $this->explicitPath));
                }

                try {
                    $version = $this->getVersion($node);
                } catch (\Throwable $e) {
                    throw new NodeBinaryNotFoundException(sprintf('Failed to get version from explicit Node.js binary %s: %s', $this->explicitPath, $e->getMessage()));
                }

                if (!version_compare($version, $this->minVersion, '>=')) {
                    throw new NodeVersionTooLowException(sprintf('Explicit Node.js version too low at %s (found %s, need >= %s).', $this->explicitPath, $version, $this->minVersion));
                }

                $this->cached = $node;

                return $node;
            }
        }

        $envOverride = getenv('PLAYWRIGHT_NODE_PATH') ?: '';
        if ('' !== $envOverride) {
            $candidates[] = $envOverride;
        }

        $finder = new ExecutableFinder();
        $foundOnPath = $finder->find('node');
        if (null !== $foundOnPath) {
            $candidates[] = $foundOnPath;
        }

        foreach ($this->knownLocations() as $path) {
            $candidates[] = $path;
        }

        foreach ($this->additionalSearchPaths as $path) {
            $candidates[] = $path;
        }

        $candidates = array_values(array_unique(array_filter($candidates)));

        foreach ($candidates as $candidate) {
            $node = $this->normalizeIfDirectory($candidate);
            if (!$this->isExecutable($node)) {
                $this->logDebug(sprintf('Node candidate not executable or missing: %s', $node));
                continue;
            }

            try {
                $version = $this->getVersion($node);
            } catch (\Throwable $e) {
                $this->logDebug(sprintf('Failed to run "%s --version": %s', $node, $e->getMessage()));
                continue;
            }

            if (!version_compare($version, $this->minVersion, '>=')) {
                $this->logDebug(sprintf('Node too old at %s (found %s, need >= %s)', $node, $version, $this->minVersion));
                continue;
            }

            $this->cached = $node;
            $this->logDebug(sprintf('Resolved Node: %s (version %s)', $node, $version));

            return $node;
        }

        $pathError = sprintf(
            'Node.js not found. Set PLAYWRIGHT_NODE_PATH or ensure "node" is on PATH. Minimum required version: %s.',
            $this->minVersion
        );

        try {
            $nodeOnPath = $finder->find('node');
            if (null !== $nodeOnPath) {
                $foundVer = $this->getVersion($nodeOnPath);
                if (!version_compare($foundVer, $this->minVersion, '>=')) {
                    throw new NodeVersionTooLowException(sprintf('Node.js version too low at %s (found %s, need >= %s).', $nodeOnPath, $foundVer, $this->minVersion));
                }
            }
        } catch (NodeVersionTooLowException $e) {
            throw $e;
        } catch (\Throwable) {
            
        }

        throw new NodeBinaryNotFoundException($pathError);
    }

    /**
     * Return Node.js version (e.g., "20.13.1") for the provided binary path.
     *
     * @throws \RuntimeException if process fails
     */
    public function getVersion(string $nodeBinary): string
    {
        $process = new Process([$nodeBinary, '--version']);
        $process->setTimeout(5.0);
        $process->setIdleTimeout(5.0);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('Failed to execute %s --version: %s', $nodeBinary, $process->getErrorOutput()));
        }

        $out = trim($process->getOutput().$process->getErrorOutput());
        
        $out = ltrim($out, 'vV');
        
        if (!preg_match('/^\d+\.\d+\.\d+/', $out, $m)) {
            throw new \RuntimeException(sprintf('Unexpected Node version output from %s: %s', $nodeBinary, $out));
        }

        return $m[0];
    }

    private function isExecutable(string $path): bool
    {
        if ('' === $path) {
            return false;
        }

        if (is_file($path)) {
            if ($this->isWindows()) {
                return preg_match('/\.(exe|cmd|bat)$/i', $path) && is_readable($path);
            }

            return is_executable($path);
        }

        return false;
    }

    /**
     * If a directory was given, append the expected node filename for the platform.
     */
    private function normalizeIfDirectory(string $candidate): string
    {
        if (is_dir($candidate)) {
            return rtrim($candidate, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.($this->isWindows() ? 'node.exe' : 'node');
        }

        return $candidate;
    }

    /**
     * Common Node install locations beyond PATH to help when PATH is restricted (services/CI).
     *
     * @return array<int, string>
     */
    private function knownLocations(): array
    {
        $paths = [];

        $home = rtrim((string) getenv('HOME') ?: (string) getenv('USERPROFILE'), DIRECTORY_SEPARATOR);
        $programFiles = (string) getenv('ProgramFiles');
        $localAppData = (string) getenv('LOCALAPPDATA');
        $nvmHome = (string) getenv('NVM_HOME');
        $asdfDir = (string) getenv('ASDF_DIR') ?: ('' !== $home ? $home.DIRECTORY_SEPARATOR.'.asdf' : '');

        if ($this->isWindows()) {
            
            if ('' !== $programFiles) {
                $paths[] = $programFiles.'\\nodejs\\node.exe';
            }
            if ('' !== $localAppData) {
                
                $paths[] = $localAppData.'\\Programs\\nodejs\\node.exe';
            }
            
            $paths[] = 'C:\\ProgramData\\chocolatey\\bin\\node.exe';

            
            if ('' !== $nvmHome) {
                $paths = array_merge($paths, $this->globNewest($nvmHome.'\\v*\\node.exe'));
            }
        } else {
            
            $paths[] = '/opt/homebrew/bin/node';
            $paths[] = '/usr/local/bin/node';

            
            if ('' !== $home) {
                $paths = array_merge($paths, $this->globNewest($home.'/.nvm/versions/node/*/bin/node'));
            }

            
            if ('' !== $asdfDir) {
                $paths[] = $asdfDir.'/shims/node';
            }
        }

        return $paths;
    }

    /**
     * Return the newest path by semantic folder name (e.g., v20.11.1), but keep all in order newest→oldest.
     *
     * @param string $pattern glob pattern
     *
     * @return array<int, string>
     */
    private function globNewest(string $pattern): array
    {
        $matches = glob($pattern, GLOB_NOSORT) ?: [];
        if ([] === $matches) {
            return [];
        }

        usort($matches, static function (string $a, string $b): int {
            
            $va = self::extractVersionFromPath($a);
            $vb = self::extractVersionFromPath($b);
            if (null === $va && null === $vb) {
                return strcmp($b, $a); 
            }
            if (null === $va) {
                return 1;
            }
            if (null === $vb) {
                return -1;
            }

            return version_compare($vb, $va); 
        });

        return $matches;
    }

    private static function extractVersionFromPath(string $path): ?string
    {
        if (preg_match('/[vV]?(\d+\.\d+\.\d+)/', $path, $m)) {
            return $m[1];
        }

        return null;
    }

    private function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }

    private function logDebug(string $message): void
    {
        if (null !== $this->logger) {
            $this->logger->debug($message);
        }
    }
}
