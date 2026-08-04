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

namespace Playwright\Tests\Unit\Browser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserBuilder;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Tests\Mocks\TestLogger;
use Playwright\Transport\TransportInterface;

#[CoversClass(BrowserBuilder::class)]
final class BrowserBuilderConnectTest extends TestCase
{
    private const WS_ENDPOINT = 'ws://admin:s3cr3t@127.0.0.1:9222/devtools';
    private const CDP_ENDPOINT = 'http://admin:s3cr3t@127.0.0.1:9222/';

    public function testConnectKeepsEndpointCredentialsOutOfTheLog(): void
    {
        $logger = new TestLogger();
        $sent = [];

        $this->builder($logger, $sent)->connect(self::WS_ENDPOINT);

        $context = $logger->records[0]['context'];
        self::assertSame('ws://[REDACTED]@127.0.0.1:9222/devtools', $context['wsEndpoint']);
        self::assertStringNotContainsString('s3cr3t', json_encode($logger->records) ?: '');
    }

    public function testConnectKeepsSensitiveOptionsOutOfTheLog(): void
    {
        $logger = new TestLogger();
        $sent = [];

        $this->builder($logger, $sent)->connect(self::WS_ENDPOINT, ['headers' => ['authorization' => 'Bearer t0ken']]);

        $context = $logger->records[0]['context'];
        self::assertSame('[REDACTED]', $context['options']['headers']['authorization']);
    }

    public function testConnectSendsTheEndpointAndOptionsUnchanged(): void
    {
        $logger = new TestLogger();
        $sent = [];

        $this->builder($logger, $sent)->connect(self::WS_ENDPOINT, ['headers' => ['authorization' => 'Bearer t0ken']]);

        self::assertSame(self::WS_ENDPOINT, $sent[0]['wsEndpoint']);
        self::assertSame(['headers' => ['authorization' => 'Bearer t0ken']], $sent[0]['options']);
    }

    public function testConnectOverCdpKeepsEndpointCredentialsOutOfTheLog(): void
    {
        $logger = new TestLogger();
        $sent = [];

        $this->builder($logger, $sent)->connectOverCDP(self::CDP_ENDPOINT);

        $context = $logger->records[0]['context'];
        self::assertSame('http://[REDACTED]@127.0.0.1:9222/', $context['endpointURL']);
        self::assertStringNotContainsString('s3cr3t', json_encode($logger->records) ?: '');
    }

    public function testConnectOverCdpKeepsSensitiveOptionsOutOfTheLog(): void
    {
        $logger = new TestLogger();
        $sent = [];

        $this->builder($logger, $sent)->connectOverCDP(self::CDP_ENDPOINT, ['headers' => ['authorization' => 'Bearer t0ken']]);

        $context = $logger->records[0]['context'];
        self::assertSame('[REDACTED]', $context['options']['headers']['authorization']);
    }

    public function testConnectOverCdpSendsTheEndpointAndOptionsUnchanged(): void
    {
        $logger = new TestLogger();
        $sent = [];

        $this->builder($logger, $sent)->connectOverCDP(self::CDP_ENDPOINT, ['headers' => ['authorization' => 'Bearer t0ken']]);

        self::assertSame(self::CDP_ENDPOINT, $sent[0]['endpointURL']);
        self::assertSame(['headers' => ['authorization' => 'Bearer t0ken']], $sent[0]['options']);
    }

    /**
     * @param array<int, array<string, mixed>> $sent
     */
    private function builder(TestLogger $logger, array &$sent): BrowserBuilder
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')->willReturnCallback(function (array $payload) use (&$sent): array {
            $sent[] = $payload;

            return ['browserId' => 'b1', 'defaultContextId' => 'ctx1', 'version' => '1.0'];
        });

        return new BrowserBuilder('chromium', $transport, $logger, new PlaywrightConfig());
    }
}
