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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserBuilder;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Exception\PlaywrightException;
use Playwright\PlaywrightClient;
use Playwright\Transport\MockTransport;
use Psr\Log\NullLogger;

#[CoversClass(BrowserBuilder::class)]
#[CoversClass(PlaywrightClient::class)]
final class BrowserBuilderRemoteEndpointTest extends TestCase
{
    #[DataProvider('browsers')]
    public function testLaunchConnectsToTheConfiguredEndpoint(string $browserType): void
    {
        $transport = new MockTransport();
        $transport->queueResponse(['browserId' => 'browser', 'defaultContextId' => 'context', 'version' => '1.0']);
        $config = new PlaywrightConfig(
            channel: 'local-channel',
            headless: false,
            timeoutMs: 12000,
            slowMoMs: 25,
            args: ['--local-only'],
            videosDir: '/tmp/videos',
            wsEndpoint: 'ws://playwright:3000/',
        );
        $client = new PlaywrightClient($transport, new NullLogger(), $config);
        $builder = $client->{$browserType}();
        $builder->launch();

        $this->assertSame([[
            'action' => 'connect',
            'browser' => $browserType,
            'wsEndpoint' => 'ws://playwright:3000/',
            'options' => ['timeout' => 12000, 'slowMo' => 25],
            'contextOptions' => ['recordVideo' => ['dir' => '/tmp/videos']],
        ]], $transport->getSentMessages());
    }

    public static function browsers(): iterable
    {
        yield 'chromium' => ['chromium'];
        yield 'firefox' => ['firefox'];
        yield 'webkit' => ['webkit'];
    }

    public function testExplicitConnectOverridesTheConfiguredEndpoint(): void
    {
        $transport = new MockTransport();
        $transport->queueResponse(['browserId' => 'browser', 'defaultContextId' => 'context', 'version' => '1.0']);
        $config = new PlaywrightConfig(wsEndpoint: 'ws://configured:3000/');
        $client = new PlaywrightClient($transport, new NullLogger(), $config);
        $builder = $client->chromium();
        $builder->connect('ws://explicit:3000/', ['timeout' => 500]);

        $messages = $transport->getSentMessages();
        $this->assertSame('ws://explicit:3000/', $messages[0]['wsEndpoint']);
        $this->assertSame(['timeout' => 500], $messages[0]['options']);
    }

    public function testLaunchPreservesAnUnlimitedConnectionTimeout(): void
    {
        $transport = new MockTransport();
        $transport->queueResponse(['browserId' => 'browser', 'defaultContextId' => 'context', 'version' => '1.0']);
        $config = new PlaywrightConfig(timeoutMs: 0, wsEndpoint: 'ws://playwright:3000/');
        $client = new PlaywrightClient($transport, new NullLogger(), $config);
        $builder = $client->chromium();
        $builder->launch();

        $messages = $transport->getSentMessages();
        $this->assertSame(['timeout' => 0, 'slowMo' => 0], $messages[0]['options']);
    }

    public function testAnUnavailableEndpointDoesNotFallBackToALocalBrowser(): void
    {
        $transport = new MockTransport();
        $transport->queueResponse(new PlaywrightException('Remote browser unavailable'));
        $config = new PlaywrightConfig(wsEndpoint: 'ws://playwright:3000/');
        $client = new PlaywrightClient($transport, new NullLogger(), $config);
        $builder = $client->chromium();

        $this->expectException(PlaywrightException::class);
        $this->expectExceptionMessage('Remote browser unavailable');

        $builder->launch();
    }
}
