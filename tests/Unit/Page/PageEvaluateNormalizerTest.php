<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Browser\BrowserContextInterface;
use PlaywrightPHP\Page\Page;
use PlaywrightPHP\Transport\TransportInterface;

#[CoversClass(Page::class)]
final class PageEvaluateNormalizerTest extends TestCase
{
    public function testNormalizesReturnBodyToFunction(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);

        $transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'page.evaluate' === $payload['action']
                    && '(arg) => { return 42; }' === $payload['expression'];
            }))
            ->willReturn(['result' => 42]);

        $page = new Page($transport, $context, 'p1');
        $result = $page->evaluate('return 42;');
        $this->assertSame(42, $result);
    }

    public function testLeavesPlainExpressionUntouched(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);

        $transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'page.evaluate' === $payload['action']
                    && 'document.title' === $payload['expression'];
            }))
            ->willReturn(['result' => 'Hello']);

        $page = new Page($transport, $context, 'p1');
        $result = $page->evaluate('document.title');
        $this->assertSame('Hello', $result);
    }
}
