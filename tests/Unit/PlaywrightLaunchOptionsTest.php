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

namespace Playwright\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Playwright;
use Playwright\PlaywrightClient;
use Playwright\Transport\MockTransport;
use Psr\Log\NullLogger;

#[CoversClass(Playwright::class)]
final class PlaywrightLaunchOptionsTest extends TestCase
{
    private MockTransport $transport;
    private ?PlaywrightClient $previousClient = null;

    protected function setUp(): void
    {
        $property = new \ReflectionProperty(Playwright::class, 'client');
        $previousClient = $property->getValue();
        $this->previousClient = $previousClient;
        $transport = new MockTransport();
        $transport->queueResponse(['browserId' => 'browser_1', 'defaultContextId' => 'context_1', 'version' => '1.0']);
        $this->transport = $transport;
        $client = new PlaywrightClient($transport, new NullLogger(), new PlaywrightConfig());
        $property->setValue(null, $client);
    }

    protected function tearDown(): void
    {
        $property = new \ReflectionProperty(Playwright::class, 'client');
        $previousClient = $this->previousClient;
        $property->setValue(null, $previousClient);
        $transport = $this->transport;
        $transport->disconnect();
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>, string}>
     */
    public static function unsupportedOptions(): iterable
    {
        yield 'chromium channel' => ['chromium', ['channel' => 'nonesuch'], 'channel'];
        yield 'firefox proxy' => ['firefox', ['proxy' => ['server' => 'http://proxy.test:8080']], 'proxy'];
        yield 'webkit context option' => ['webkit', ['ignoreHTTPSErrors' => true], 'ignoreHTTPSErrors'];
        yield 'safari typo' => ['safari', ['headles' => true], 'headles'];
        yield 'multiple options' => ['chromium', ['headless' => true, 'channel' => 'chrome', 'proxy' => []], 'channel, proxy'];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('unsupportedOptions')]
    public function testRejectsUnsupportedOptionsBeforeConnecting(string $browserMethod, array $options, string $unsupportedOptions): void
    {
        try {
            Playwright::{$browserMethod}($options);
            $this->fail('Unsupported launch options must not be silently ignored.');
        } catch (\InvalidArgumentException $exception) {
            $message = $exception->getMessage();
            $this->assertStringContainsString('Unsupported launch option(s): '.$unsupportedOptions, $message);
            $this->assertStringContainsString('PlaywrightFactory::create()', $message);
        }

        $transport = $this->transport;
        $messages = $transport->getSentMessages();
        $connected = $transport->isConnected();

        $this->assertSame([], $messages);
        $this->assertFalse($connected);
    }

    public function testSupportedOptionsStillReachTheBrowserAndContext(): void
    {
        $transport = $this->transport;
        $transport->queueResponse(['contextId' => 'context_2']);

        Playwright::chromium([
            'headless' => false,
            'slowMo' => 125,
            'args' => ['--test-argument'],
            'context' => ['ignoreHTTPSErrors' => true],
        ]);

        $messages = $transport->getSentMessages();

        $this->assertSame(['headless' => false, 'slowMo' => 125, 'args' => ['--test-argument']], $messages[0]['options']);
        $this->assertSame('newContext', $messages[1]['action']);
        $this->assertSame(['ignoreHTTPSErrors' => true], $messages[1]['options']);
    }

    public function testLaunchingWithoutOptionsStillUsesTheDefaultContext(): void
    {
        Playwright::chromium();
        $transport = $this->transport;
        $messages = $transport->getSentMessages();

        $this->assertSame(['launch'], array_column($messages, 'action'));
    }
}
