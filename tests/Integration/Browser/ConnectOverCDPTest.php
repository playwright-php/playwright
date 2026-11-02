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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserBuilder;
use Playwright\Browser\BrowserInterface;
use Playwright\PlaywrightClient;
use Playwright\PlaywrightFactory;

#[CoversClass(BrowserBuilder::class)]
#[Group('integration')]
final class ConnectOverCDPTest extends TestCase
{
    private PlaywrightClient $browserOwner;
    private string $endpointURL;

    /** @var list<PlaywrightClient> */
    private array $clients = [];

    protected function setUp(): void
    {
        $port = $this->freePort();
        $this->endpointURL = "http://127.0.0.1:{$port}";
        $this->browserOwner = PlaywrightFactory::create();
        $this->browserOwner->chromium()
            ->withHeadless(true)
            ->withArgs(["--remote-debugging-port={$port}"])
            ->launch();
    }

    protected function tearDown(): void
    {
        foreach ($this->clients as $client) {
            $client->close();
        }
        $this->browserOwner->close();
    }

    #[Test]
    public function itListsThePagesAlreadyOpenInTheBrowser(): void
    {
        $page = $this->connect()->context()->newPage();
        $page->setContent('<h1>Already open</h1>');

        $pages = $this->connect()->context()->pages();

        $this->assertCount(1, $pages);
        $this->assertSame('Already open', $pages[0]->locator('h1')->textContent());
    }

    #[Test]
    public function itKeepsThePageAndItsStateAcrossADisconnect(): void
    {
        $firstClient = PlaywrightFactory::create();
        $page = $firstClient->chromium()->connectOverCDP($this->endpointURL)->context()->newPage();
        $page->setContent('<input id="name">');
        $page->locator('#name')->fill('typed before the disconnect');
        $firstClient->close();

        $pages = $this->connect()->context()->pages();

        $this->assertCount(1, $pages);
        $this->assertSame('typed before the disconnect', $pages[0]->locator('#name')->inputValue());
    }

    #[Test]
    public function itKeepsThePageWhenTheBrowserHandleCloses(): void
    {
        $browser = $this->connect();
        $page = $browser->context()->newPage();
        $page->setContent('<h1>Still here</h1>');
        $browser->close();

        $pages = $this->connect()->context()->pages();

        $this->assertCount(1, $pages);
        $this->assertSame('Still here', $pages[0]->locator('h1')->textContent());
    }

    private function connect(): BrowserInterface
    {
        $client = PlaywrightFactory::create();
        $this->clients[] = $client;

        return $client->chromium()->connectOverCDP($this->endpointURL);
    }

    private function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $this->assertNotFalse($socket);
        $address = stream_socket_get_name($socket, false);
        $this->assertNotFalse($address);
        fclose($socket);

        return (int) substr($address, (int) strrpos($address, ':') + 1);
    }
}
