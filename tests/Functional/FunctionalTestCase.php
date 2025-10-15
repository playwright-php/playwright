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

namespace Playwright\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Symfony\Component\Process\Process;

/**
 * Base class for functional tests using real Playwright browsers.
 *
 * Provides browser lifecycle management and common utilities.
 */
abstract class FunctionalTestCase extends TestCase
{
    use PlaywrightTestCaseTrait;

    // Auto-managed fixture server for local runs (when TEST_SERVER_BASE_URL is not set)
    protected static ?Process $fixtureServer = null;
    protected static ?string $autoBaseUrl = null;

    /**
     * Start Playwright and (if needed) a local fixture server once for all tests in the class.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // If CI or caller provides a server URL, do not auto-start
        $providedBase = $_ENV['TEST_SERVER_BASE_URL']
            ?? (getenv('TEST_SERVER_BASE_URL') ?: null)
            ?? $_ENV['FIXTURE_SERVER_BASE_URL']
            ?? (getenv('FIXTURE_SERVER_BASE_URL') ?: null);

        if (null === $providedBase) {
            // Auto-start a local server serving tests/Fixtures via router
            $host = '127.0.0.1';
            $port = 8888;
            if (!self::isPortAvailable($host, $port)) {
                $port = self::findAvailablePort($host) ?: $port;
            }

            $fixturesDir = \realpath(__DIR__.'/../Fixtures');
            if (false === $fixturesDir) {
                throw new \RuntimeException('Fixtures directory not found');
            }
            $router = $fixturesDir.'/server.php';
            if (!\file_exists($router)) {
                throw new \RuntimeException('Router script not found: '.$router);
            }

            self::$fixtureServer = new Process([
                PHP_BINARY,
                '-S', sprintf('%s:%d', $host, $port),
                '-t', $fixturesDir,
                $router,
            ], $fixturesDir);
            self::$fixtureServer->setTimeout(null);
            self::$fixtureServer->setIdleTimeout(null);
            self::$fixtureServer->start();

            // Wait until ready (index.html should exist according to fixtures)
            $deadline = time() + 10; // 10s max
            $ready = false;
            while (time() < $deadline) {
                if (self::isServerReady($host, $port)) {
                    $ready = true;
                    break;
                }
                usleep(100_000);
            }

            if (!$ready) {
                $stderr = '';
                if (self::$fixtureServer?->isStarted()) {
                    $stderr = trim(self::$fixtureServer->getErrorOutput());
                }
                self::$fixtureServer?->stop();
                throw new \RuntimeException('Fixture server failed to start'.('' !== $stderr ? ' - stderr: '.$stderr : ''));
            }

            self::$autoBaseUrl = sprintf('http://%s:%d', $host, $port);

            // Ensure cleanup at process end
            register_shutdown_function(static function (): void {
                if (null !== self::$fixtureServer && self::$fixtureServer->isRunning()) {
                    self::$fixtureServer->stop();
                    self::$fixtureServer = null;
                }
            });
        }
    }

    /**
     * Create new browser context and page for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPlaywright();
    }

    /**
     * Close context after each test.
     */
    protected function tearDown(): void
    {
        $this->tearDownPlaywright();

        parent::tearDown();
    }

    /**
     * Close browser after all tests in the class.
     */
    public static function tearDownAfterClass(): void
    {
        self::closeSharedPlaywright();

        // Stop auto-started server if any
        if (null !== self::$fixtureServer && self::$fixtureServer->isRunning()) {
            self::$fixtureServer->stop();
            self::$fixtureServer = null;
        }

        parent::tearDownAfterClass();
    }

    /**
     * Get the base URL for test fixtures.
     *
     * Respects TEST_SERVER_BASE_URL (or FIXTURE_SERVER_BASE_URL) env vars; defaults to http://127.0.0.1:8888.
     * If auto server is started, returns that server's base URL.
     */
    protected function getBaseUrl(): string
    {
        $base = $_ENV['TEST_SERVER_BASE_URL']
            ?? (getenv('TEST_SERVER_BASE_URL') ?: null)
            ?? $_ENV['FIXTURE_SERVER_BASE_URL']
            ?? (getenv('FIXTURE_SERVER_BASE_URL') ?: null)
            ?? self::$autoBaseUrl
            ?? 'http://127.0.0.1:8888';

        return rtrim($base, '/');
    }

    /**
     * Navigate to a fixture page.
     */
    protected function goto(string $path): void
    {
        $url = $this->getBaseUrl().$path;
        $this->page->goto($url);
    }

    /**
     * Assert that the current URL contains the given string.
     */
    protected function assertUrlContains(string $needle): void
    {
        $currentUrl = $this->page->url();
        self::assertStringContainsString(
            $needle,
            $currentUrl,
            \sprintf('Expected URL to contain "%s", got "%s"', $needle, $currentUrl)
        );
    }

    /**
     * Assert that the current URL equals the given URL.
     */
    protected function assertUrlEquals(string $expected): void
    {
        $currentUrl = $this->page->url();
        self::assertSame(
            $expected,
            $currentUrl,
            \sprintf('Expected URL to be "%s", got "%s"', $expected, $currentUrl)
        );
    }

    /**
     * Assert that an element with the given selector exists.
     */
    protected function assertElementExists(string $selector): void
    {
        $locator = $this->page->locator($selector);
        $count = $locator->count();

        self::assertGreaterThan(
            0,
            $count,
            \sprintf('Expected element matching selector "%s" to exist', $selector)
        );
    }

    /**
     * Assert that an element with the given selector is visible.
     */
    protected function assertElementVisible(string $selector): void
    {
        $locator = $this->page->locator($selector);
        $isVisible = $locator->isVisible();

        self::assertTrue(
            $isVisible,
            \sprintf('Expected element matching selector "%s" to be visible', $selector)
        );
    }

    /**
     * Assert that an element with the given selector has the expected text.
     */
    protected function assertElementHasText(string $selector, string $expectedText): void
    {
        $locator = $this->page->locator($selector);
        $actualText = $locator->textContent();

        self::assertSame(
            $expectedText,
            $actualText,
            \sprintf('Expected element "%s" to have text "%s", got "%s"', $selector, $expectedText, $actualText)
        );
    }

    private static function isPortAvailable(string $host, int $port): bool
    {
        $conn = @fsockopen($host, $port, $errno, $errstr, 0.5);
        if (is_resource($conn)) {
            fclose($conn);

            return false;
        }

        return true;
    }

    private static function findAvailablePort(string $host): int
    {
        for ($i = 0; $i < 30; ++$i) {
            $port = random_int(8000, 9000);
            if (self::isPortAvailable($host, $port)) {
                return $port;
            }
        }

        return 0;
    }

    private static function isServerReady(string $host, int $port): bool
    {
        $url = sprintf('http://%s:%d/index.html', $host, $port);
        $ctx = stream_context_create(['http' => ['timeout' => 0.5, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $ctx);

        return false !== $body;
    }
}
