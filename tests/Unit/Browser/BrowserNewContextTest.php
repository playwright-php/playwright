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
use Playwright\Browser\Browser;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Transport\TransportInterface;

#[CoversClass(Browser::class)]
final class BrowserNewContextTest extends TestCase
{
    public function testNewContextRecordsVideoWhenTheConfigSetsVideosDir(): void
    {
        $sent = [];
        $transport = $this->recordingTransport($sent);

        $browser = new Browser($transport, 'b', 'ctx_default', '1.0', new PlaywrightConfig(
            videosDir: '/tmp/videos',
        ));

        $browser->newContext();

        $this->assertSame(['dir' => '/tmp/videos'], $sent[0]['options']['recordVideo']);
    }

    public function testNewContextLeavesRecordVideoOutWhenVideosDirIsUnset(): void
    {
        $sent = [];
        $transport = $this->recordingTransport($sent);

        $browser = new Browser($transport, 'b', 'ctx_default', '1.0', new PlaywrightConfig());

        $browser->newContext();

        $this->assertArrayNotHasKey('recordVideo', $sent[0]['options']);
    }

    public function testExplicitOptionsOverrideTheConfig(): void
    {
        $sent = [];
        $transport = $this->recordingTransport($sent);

        $browser = new Browser($transport, 'b', 'ctx_default', '1.0', new PlaywrightConfig(
            videosDir: '/tmp/videos',
        ));

        $browser->newContext(['recordVideo' => ['dir' => '/elsewhere']]);

        $this->assertSame(['dir' => '/elsewhere'], $sent[0]['options']['recordVideo']);
    }

    /**
     * @param array<int, array<string, mixed>> $sent
     */
    private function recordingTransport(array &$sent): TransportInterface
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')->willReturnCallback(function (array $payload) use (&$sent): array {
            $sent[] = $payload;

            return ['contextId' => 'ctx_new'];
        });

        return $transport;
    }
}
