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

namespace Playwright\Tests\Support;

use PHPUnit\Framework\TestCase;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Symfony\Component\Process\Process;

/**
 * Base class for functional tests that need a real HTTP server serving fixtures.
 *
 * This class automatically starts a PHP built-in server before tests run
 * and stops it after tests complete. The server serves static HTML files
 * from the tests/Fixtures directory.
 */
abstract class FunctionalTestCase extends TestCase
{
    use PlaywrightTestCaseTrait;

    protected static ?Process $fixtureServer = null;
    protected static string $fixtureServerHost;
    protected static int $fixtureServerPort;
    protected static string $fixtureServerBaseUrl;

    /**
     * Start the fixture server before any tests in the class run.
     */
    public static function setUpBeforeClass(): void
    {
        self::$fixtureServerHost = $_ENV['FIXTURE_SERVER_HOST'] ?? '127.0.0.1';
        self::$fixtureServerPort = (int) ($_ENV['FIXTURE_SERVER_PORT'] ?? 8765);
        self::$fixtureServerBaseUrl = $_ENV['FIXTURE_SERVER_BASE_URL'] ?? sprintf('http://%s:%d', self::$fixtureServerHost, self::$fixtureServerPort);

        if (!self::isPortAvailable(self::$fixtureServerHost, self::$fixtureServerPort)) {
            throw new \RuntimeException(\sprintf('Port %d is already in use on %s. Cannot start fixture server.', self::$fixtureServerPort, self::$fixtureServerHost));
        }

        $fixturesDir = __DIR__.'/../Fixtures';
        $serverScript = $fixturesDir.'/server.php';

        if (!file_exists($serverScript)) {
            throw new \RuntimeException(\sprintf('Server script not found at %s', $serverScript));
        }

        self::$fixtureServer = new Process([
            PHP_BINARY,
            '-S',
            self::$fixtureServerHost.':'.self::$fixtureServerPort,
            '-t',
            $fixturesDir,
            $serverScript,
        ], $fixturesDir);

        self::$fixtureServer->start();

        // Wait for server to be ready
        $maxWaitTime = 5;
        $startTime = \time();
        while (\time() <= $startTime + $maxWaitTime) {
            if (self::isServerReady(self::$fixtureServerHost, self::$fixtureServerPort)) {
                break;
            }
            \usleep(100000); // 100ms
        }

        if (!self::isServerReady(self::$fixtureServerHost, self::$fixtureServerPort)) {
            self::$fixtureServer->stop();
            throw new \RuntimeException(\sprintf('Fixture server did not start within %d seconds', $maxWaitTime));
        }

        // Register shutdown function to ensure cleanup
        \register_shutdown_function([static::class, 'stopFixtureServer']);
    }

    /**
     * Stop the fixture server after all tests in the class complete.
     */
    public static function tearDownAfterClass(): void
    {
        self::stopFixtureServer();
    }

    /**
     * Stop the fixture server if it's running.
     */
    public static function stopFixtureServer(): void
    {
        if (null !== self::$fixtureServer && self::$fixtureServer->isRunning()) {
            self::$fixtureServer->stop();
            self::$fixtureServer = null;
        }
    }

    /**
     * Set up Playwright for each test.
     */
    protected function setUp(): void
    {
        $this->setUpPlaywright();
    }

    /**
     * Tear down Playwright after each test.
     */
    protected function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    /**
     * Get the base URL for the fixture server.
     */
    protected function fixtureUrl(string $path = '/'): string
    {
        $path = '/' === $path ? '/' : '/'.ltrim($path, '/');

        return self::$fixtureServerBaseUrl.$path;
    }

    /**
     * Check if a port is available for use.
     */
    private static function isPortAvailable(string $host, int $port): bool
    {
        $connection = @\fsockopen($host, $port, $errno, $errstr, 1);
        if (\is_resource($connection)) {
            \fclose($connection);

            return false;
        }

        return true;
    }

    /**
     * Check if the server is ready to accept connections.
     */
    private static function isServerReady(string $host, int $port): bool
    {
        $connection = @\fsockopen($host, $port, $errno, $errstr, 1);
        if (\is_resource($connection)) {
            \fclose($connection);

            return true;
        }

        return false;
    }
}
