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

namespace Playwright\Tests\Integration\Browser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserBuilder;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\PlaywrightClient;
use Playwright\PlaywrightFactory;

#[CoversClass(BrowserBuilder::class)]
final class RemoteEndpointTest extends TestCase
{
    private PlaywrightClient $browserOwner;
    private ?PlaywrightClient $remoteClient = null;

    protected function setUp(): void
    {
        $this->browserOwner = PlaywrightFactory::create();
    }

    protected function tearDown(): void
    {
        $this->remoteClient?->close();
        $this->browserOwner->close();
    }

    public function testLaunchUsesTheRemoteServerAndLeavesItAvailableAfterClosing(): void
    {
        $server = $this->browserOwner->launchServer('chromium', ['headless' => true]);
        $config = new PlaywrightConfig(channel: 'invalid-local-channel', wsEndpoint: $server->wsEndpoint());
        $this->remoteClient = PlaywrightFactory::create($config);
        $builder = $this->remoteClient->chromium();
        $browser = $builder->launch();
        $context = $browser->context();
        $page = $context->newPage();
        $page->setContent('<title>Remote browser</title>');

        $this->assertSame('Remote browser', $page->title());
        $browser->close();

        $browser = $builder->launch();
        $this->assertTrue($browser->isConnected());
        $browser->close();
    }
}
