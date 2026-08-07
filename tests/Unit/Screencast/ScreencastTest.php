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

namespace Playwright\Tests\Unit\Screencast;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Exception\PlaywrightException;
use Playwright\Screencast\Screencast;
use Playwright\Transport\MockTransport;

#[CoversClass(Screencast::class)]
final class ScreencastTest extends TestCase
{
    public function testStartSendsOptions(): void
    {
        $transport = $this->transport();
        $transport->queueResponse([]);

        $options = [
            'path' => '/tmp/video.webm',
            'size' => ['width' => 640, 'height' => 480],
            'quality' => 80,
        ];
        (new Screencast($transport, 'page_1'))->start($options);

        $sent = $transport->getSentMessages();
        $this->assertCount(1, $sent);
        $this->assertSame('screencast.start', $sent[0]['action']);
        $this->assertSame('page_1', $sent[0]['pageId']);
        $this->assertSame($options, $sent[0]['options']);
    }

    public function testStartDefaultsToEmptyOptions(): void
    {
        $transport = $this->transport();
        $transport->queueResponse([]);

        (new Screencast($transport, 'page_2'))->start();

        $this->assertSame([], $transport->getSentMessages()[0]['options']);
    }

    public function testStopSendsAction(): void
    {
        $transport = $this->transport();
        $transport->queueResponse([]);

        (new Screencast($transport, 'page_3'))->stop();

        $sent = $transport->getSentMessages();
        $this->assertSame('screencast.stop', $sent[0]['action']);
        $this->assertSame('page_3', $sent[0]['pageId']);
    }

    public function testShowOverlaySendsHtmlAndOptions(): void
    {
        $transport = $this->transport();
        $transport->queueResponse([]);

        (new Screencast($transport, 'page_4'))->showOverlay('<b>hi</b>', ['duration' => 500]);

        $sent = $transport->getSentMessages();
        $this->assertSame('screencast.showOverlay', $sent[0]['action']);
        $this->assertSame('<b>hi</b>', $sent[0]['html']);
        $this->assertSame(['duration' => 500], $sent[0]['options']);
    }

    public function testShowOverlaysSendsAction(): void
    {
        $transport = $this->transport();
        $transport->queueResponse([]);

        (new Screencast($transport, 'page_5'))->showOverlays();

        $this->assertSame('screencast.showOverlays', $transport->getSentMessages()[0]['action']);
    }

    public function testHideOverlaysSendsAction(): void
    {
        $transport = $this->transport();
        $transport->queueResponse([]);

        (new Screencast($transport, 'page_6'))->hideOverlays();

        $this->assertSame('screencast.hideOverlays', $transport->getSentMessages()[0]['action']);
    }

    public function testShowActionsSendsOptions(): void
    {
        $transport = $this->transport();
        $transport->queueResponse([]);

        $options = ['cursor' => 'pointer', 'duration' => 300, 'fontSize' => 20, 'position' => 'top-right'];
        (new Screencast($transport, 'page_7'))->showActions($options);

        $sent = $transport->getSentMessages();
        $this->assertSame('screencast.showActions', $sent[0]['action']);
        $this->assertSame($options, $sent[0]['options']);
    }

    public function testHideActionsSendsAction(): void
    {
        $transport = $this->transport();
        $transport->queueResponse([]);

        (new Screencast($transport, 'page_8'))->hideActions();

        $this->assertSame('screencast.hideActions', $transport->getSentMessages()[0]['action']);
    }

    public function testShowChapterSendsTitleAndOptions(): void
    {
        $transport = $this->transport();
        $transport->queueResponse([]);

        (new Screencast($transport, 'page_9'))->showChapter('Step 1', ['description' => 'Sign in', 'duration' => 1500]);

        $sent = $transport->getSentMessages();
        $this->assertSame('screencast.showChapter', $sent[0]['action']);
        $this->assertSame('Step 1', $sent[0]['title']);
        $this->assertSame(['description' => 'Sign in', 'duration' => 1500], $sent[0]['options']);
    }

    public function testShowChapterDefaultsToEmptyOptions(): void
    {
        $transport = $this->transport();
        $transport->queueResponse([]);

        (new Screencast($transport, 'page_10'))->showChapter('Step 2');

        $this->assertSame([], $transport->getSentMessages()[0]['options']);
    }

    public function testShowOverlayDefaultsToEmptyOptions(): void
    {
        $transport = $this->transport();
        $transport->queueResponse([]);

        (new Screencast($transport, 'page_11'))->showOverlay('<i>x</i>');

        $this->assertSame([], $transport->getSentMessages()[0]['options']);
    }

    public function testShowActionsDefaultsToEmptyOptions(): void
    {
        $transport = $this->transport();
        $transport->queueResponse([]);

        (new Screencast($transport, 'page_12'))->showActions();

        $this->assertSame([], $transport->getSentMessages()[0]['options']);
    }

    public function testItRaisesServerErrors(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['error' => 'Screencast is already started']);

        $this->expectException(PlaywrightException::class);
        $this->expectExceptionMessage('Screencast is already started');

        (new Screencast($transport, 'page_err'))->start();
    }

    private function transport(): MockTransport
    {
        $transport = new MockTransport();
        $transport->connect();

        return $transport;
    }
}
