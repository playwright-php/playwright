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

namespace Playwright\Testing;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Browser\BrowserInterface;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Exception\RuntimeException;
use Playwright\Locator\LocatorInterface;
use Playwright\Page\PageInterface;
use Playwright\PlaywrightClient;
use Playwright\PlaywrightFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\ExecutableFinder;

trait PlaywrightTestCaseTrait
{
    protected PlaywrightClient $playwright;

    protected BrowserInterface $browser;

    protected BrowserContextInterface $context;

    protected PageInterface $page;

    protected static ?PlaywrightClient $sharedPlaywright = null;

    protected static ?BrowserInterface $sharedBrowser = null;

    private bool $usingShared = true;

    private bool $traceThisTest = false;

    protected function setUpPlaywright(?LoggerInterface $logger = null, ?PlaywrightConfig $customConfig = null): void
    {
        $logger = $this->resolveLogger($logger);

        $node = (new ExecutableFinder())->find('node');
        if (null === $node) {
            self::markTestSkipped('Node.js executable not found.');
        }

        $config = $this->buildConfig($node, $customConfig);

        if (null !== $customConfig) {
            $this->usingShared = false;
            $this->playwright = PlaywrightFactory::create($config, $logger);
            $this->browser = $this->playwright->chromium()->launch();
        } else {
            $this->initializeShared($config, $logger);
            if (null === self::$sharedPlaywright || null === self::$sharedBrowser) {
                throw new RuntimeException('Shared Playwright/Browser not initialized');
            }
            $this->playwright = self::$sharedPlaywright;
            $this->browser = self::$sharedBrowser;
        }

        $this->context = $this->browser->newContext();
        $this->page = $this->context->newPage();

        $this->traceThisTest = $this->shouldTrace();
        if ($this->traceThisTest) {
            $this->context->startTracing($this->page, [
                'screenshots' => true,
                'snapshots' => true,
            ]);
        }
    }

    protected function tearDownPlaywright(): void
    {
        if (method_exists($this, 'status')) {
            $status = $this->status();

            if ($status->isFailure() || $status->isError()) {
                $testName = method_exists($this, 'getName') && is_string($this->getName()) ? $this->getName() : 'test';
                $this->captureFailureArtifacts($testName);
            }
        }

        $this->safeClose($this->context);

        if (!$this->usingShared) {
            $this->safeClose($this->browser);
            $this->safeClose($this->playwright);
        }
    }

    protected static function closeSharedPlaywright(): void
    {
        if (null !== self::$sharedBrowser) {
            self::safeStaticClose(self::$sharedBrowser);
            self::$sharedBrowser = null;
        }
        if (null !== self::$sharedPlaywright) {
            self::safeStaticClose(self::$sharedPlaywright);
            self::$sharedPlaywright = null;
        }
    }

    protected function assertElementExists(string $selector): void
    {
        $content = $this->page->content() ?? '';
        $this->assertStringContainsString($selector, $content, "Element {$selector} not found.");
    }

    protected function expect(LocatorInterface|PageInterface $subject): ExpectInterface
    {
        return new Expect($subject);
    }

    private function resolveLogger(?LoggerInterface $logger): ?LoggerInterface
    {
        $loggerUrl = $_SERVER['PLAYWRIGHT_PHP_TEST_LOGGER_URL'] ?? null;
        if (is_string($loggerUrl)) {
            return new Logger('playwright-php-test', [new StreamHandler($loggerUrl)]);
        }

        return $logger;
    }

    private function buildConfig(string $node, ?PlaywrightConfig $custom): PlaywrightConfig
    {
        if (null === $custom) {
            return new PlaywrightConfig(nodePath: $node);
        }

        return $custom->withNodePath($node);
    }

    private function initializeShared(PlaywrightConfig $config, ?LoggerInterface $logger): void
    {
        if (null === self::$sharedPlaywright) {
            self::$sharedPlaywright = PlaywrightFactory::create($config, $logger);
        }
        if (null === self::$sharedBrowser || !self::$sharedBrowser->isConnected()) {
            self::$sharedBrowser = self::$sharedPlaywright->chromium()->launch();
        }

        static $shutdownRegistered = false;
        if (!$shutdownRegistered) {
            $shutdownRegistered = true;
            register_shutdown_function(static function (): void {
                if (self::$sharedBrowser) {
                    self::safeStaticClose(self::$sharedBrowser);
                    self::$sharedBrowser = null;
                }
                if (self::$sharedPlaywright) {
                    self::safeStaticClose(self::$sharedPlaywright);
                    self::$sharedPlaywright = null;
                }
            });
        }
    }

    private function shouldTrace(): bool
    {
        $env = $_SERVER['PW_TRACE'] ?? getenv('PW_TRACE');
        $value = is_string($env) ? $env : '';

        return '' !== $value && '0' !== $value;
    }

    private function captureFailureArtifacts(string $testName): void
    {
        $dir = getcwd().'/test-failures';
        $this->ensureDirectory($dir);
        $this->page->screenshot($dir.'/'.$testName.'.png');
        if ($this->traceThisTest) {
            $this->context->stopTracing($this->page, $dir.'/'.$testName.'.zip');
        }
    }

    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
    }

    private function safeClose(?object $resource): void
    {
        self::safeStaticClose($resource);
    }

    private static function safeStaticClose(?object $resource): void
    {
        if (null === $resource) {
            return;
        }

        try {
            if (method_exists($resource, 'close')) {
                $resource->close();
            }
        } catch (\Throwable) {
        }
    }
}
