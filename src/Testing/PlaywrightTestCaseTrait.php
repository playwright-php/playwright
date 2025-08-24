<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Testing;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PlaywrightPHP\Browser\BrowserContextInterface;
use PlaywrightPHP\Browser\BrowserInterface;
use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\Locator\LocatorInterface;
use PlaywrightPHP\Page\PageInterface;
use PlaywrightPHP\PlaywrightClient;
use PlaywrightPHP\PlaywrightFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\ExecutableFinder;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
trait PlaywrightTestCaseTrait
{
    protected PlaywrightClient $playwright;
    protected BrowserInterface $browser;
    protected BrowserContextInterface $context;
    protected PageInterface $page;

    /** Shared lifecycle across tests in a class */
    protected static ?PlaywrightClient $sharedPlaywright = null;
    protected static ?BrowserInterface $sharedBrowser = null;
    private bool $usingShared = true;
    private bool $traceThisTest = false;

    protected function setUpPlaywright(?LoggerInterface $logger = null, ?PlaywrightConfig $customConfig = null): void
    {
        if (isset($_SERVER['PLAYWRIGHT_PHP_TEST_LOGGER_URL'])) {
            $loggerUrl = $_SERVER['PLAYWRIGHT_PHP_TEST_LOGGER_URL'];
            if (is_string($loggerUrl)) {
                $logger = new Logger('playwright-php-test', [
                    new StreamHandler($loggerUrl),
                ]);
            }
        }

        $finder = new ExecutableFinder();
        $node = $finder->find('node');

        if (null === $node) {
            self::markTestSkipped('Node.js executable not found.');
        }

        if (null !== $customConfig) {
            $config = new PlaywrightConfig(
                nodePath: $node,
                minNodeVersion: $customConfig->minNodeVersion,
                browser: $customConfig->browser,
                channel: $customConfig->channel,
                headless: $customConfig->headless,
                timeoutMs: $customConfig->timeoutMs,
                slowMoMs: $customConfig->slowMoMs,
                args: $customConfig->args,
                env: $customConfig->env,
                downloadsDir: $customConfig->downloadsDir,
                videosDir: $customConfig->videosDir,
                screenshotDir: $customConfig->screenshotDir,
                tracingEnabled: $customConfig->tracingEnabled,
                traceDir: $customConfig->traceDir,
                traceScreenshots: $customConfig->traceScreenshots,
                traceSnapshots: $customConfig->traceSnapshots,
                proxy: $customConfig->proxy,
                logger: $customConfig->logger,
            );
        } else {
            $config = new PlaywrightConfig(nodePath: $node);
        }

        if (null !== $customConfig) {
            $this->usingShared = false;
            $this->playwright = PlaywrightFactory::create($config, $logger);
            $this->browser = $this->playwright->chromium()->launch();
        } else {
            if (null === self::$sharedPlaywright) {
                self::$sharedPlaywright = PlaywrightFactory::create($config, $logger);
            }
            if (null === self::$sharedBrowser || !self::$sharedBrowser->isConnected()) {
                self::$sharedBrowser = self::$sharedPlaywright->chromium()->launch();
            }

            static $shutdownRegistered = false;
            if (!$shutdownRegistered) {
                $shutdownRegistered = true;
                register_shutdown_function(function (): void {
                    if (self::$sharedBrowser) {
                        try {
                            self::$sharedBrowser->close();
                        } catch (\Throwable) {
                        }
                        self::$sharedBrowser = null;
                    }
                    if (self::$sharedPlaywright) {
                        try {
                            self::$sharedPlaywright->close();
                        } catch (\Throwable) {
                        }
                        self::$sharedPlaywright = null;
                    }
                });
            }
            $this->playwright = self::$sharedPlaywright;
            $this->browser = self::$sharedBrowser;
        }
        $this->context = $this->browser->newContext();
        $this->page = $this->context->newPage();

        $envTrace = $_SERVER['PW_TRACE'] ?? getenv('PW_TRACE');
        $this->traceThisTest = (is_string($envTrace) && '' !== $envTrace && '0' !== $envTrace);
        if ($this->traceThisTest) {
            $this->context->startTracing($this->page, [
                'screenshots' => true,
                'snapshots' => true,
            ]);
        }
    }

    protected function tearDownPlaywright(): void
    {
        $status = $this->status();

        if ($status->isFailure() || $status->isError()) {
            $failuresDir = getcwd().'/test-failures';
            if (!is_dir($failuresDir)) {
                if (!mkdir($failuresDir, 0777, true) && !is_dir($failuresDir)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $failuresDir));
                }
            }
            $testName = 'test';
            if (method_exists($this, 'getName')) {
                $name = $this->getName();
                if (is_string($name)) {
                    $testName = $name;
                }
            }
            $this->page->screenshot($failuresDir.'/'.$testName.'.png');
            if ($this->traceThisTest) {
                $this->context->stopTracing($this->page, $failuresDir.'/'.$testName.'.zip');
            }
        }

        try {
            $this->context->close();
        } catch (\Throwable) {
        }

        if (!$this->usingShared) {
            try {
                $this->browser->close();
            } catch (\Throwable) {
            }
            try {
                $this->playwright->close();
            } catch (\Throwable) {
            }
        }
    }

    /**
     * Optionally call this in tearDownAfterClass from tests to close shared resources.
     */
    protected static function closeSharedPlaywright(): void
    {
        if (self::$sharedBrowser) {
            try {
                self::$sharedBrowser->close();
            } catch (\Throwable) {
            }
            self::$sharedBrowser = null;
        }
        if (self::$sharedPlaywright) {
            try {
                self::$sharedPlaywright->close();
            } catch (\Throwable) {
            }
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
}
