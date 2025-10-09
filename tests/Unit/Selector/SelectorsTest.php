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

namespace Playwright\Tests\Unit\Selector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Selector\Selectors;
use Playwright\Transport\TransportInterface;

#[CoversClass(Selectors::class)]
final class SelectorsTest extends TestCase
{
    private TransportInterface $transport;
    private Selectors $selectors;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->selectors = new Selectors($this->transport);
    }

    public function testRegisterWithScript(): void
    {
        $script = 'element => element.id === "test"';

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) use ($script) {
                return 'selectors.register' === $payload['action']
                    && 'custom' === $payload['name']
                    && $script === $payload['script']
                    && [] === $payload['options'];
            }));

        $this->selectors->register('custom', $script);
    }

    public function testRegisterWithOptions(): void
    {
        $script = 'element => element.matches(".special")';
        $options = ['contentScript' => true];

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) use ($script, $options) {
                return 'selectors.register' === $payload['action']
                    && 'special' === $payload['name']
                    && $script === $payload['script']
                    && $options === $payload['options'];
            }));

        $this->selectors->register('special', $script, $options);
    }

    public function testSetTestIdAttribute(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'selectors.setTestIdAttribute' === $payload['action']
                    && 'data-test' === $payload['attributeName'];
            }));

        $this->selectors->setTestIdAttribute('data-test');
        $this->assertSame('data-test', $this->selectors->getTestIdAttribute());
    }

    public function testGetTestIdAttributeDefaultValue(): void
    {
        $this->assertSame('data-testid', $this->selectors->getTestIdAttribute());
    }

    public function testSetTestIdAttributeMultipleTimes(): void
    {
        $this->transport
            ->expects($this->exactly(2))
            ->method('send')
            ->willReturnCallback(function ($payload) {
                static $callCount = 0;
                ++$callCount;

                if (1 === $callCount) {
                    $this->assertSame('selectors.setTestIdAttribute', $payload['action']);
                    $this->assertSame('data-qa', $payload['attributeName']);
                } elseif (2 === $callCount) {
                    $this->assertSame('selectors.setTestIdAttribute', $payload['action']);
                    $this->assertSame('data-automation', $payload['attributeName']);
                }

                return [];
            });

        $this->selectors->setTestIdAttribute('data-qa');
        $this->assertSame('data-qa', $this->selectors->getTestIdAttribute());

        $this->selectors->setTestIdAttribute('data-automation');
        $this->assertSame('data-automation', $this->selectors->getTestIdAttribute());
    }

    public function testRegisterMultipleSelectors(): void
    {
        $this->transport
            ->expects($this->exactly(3))
            ->method('send')
            ->willReturnCallback(function ($payload) {
                static $callCount = 0;
                ++$callCount;

                $this->assertSame('selectors.register', $payload['action']);

                if (1 === $callCount) {
                    $this->assertSame('first', $payload['name']);
                } elseif (2 === $callCount) {
                    $this->assertSame('second', $payload['name']);
                } elseif (3 === $callCount) {
                    $this->assertSame('third', $payload['name']);
                }

                return [];
            });

        $this->selectors->register('first', 'script1');
        $this->selectors->register('second', 'script2');
        $this->selectors->register('third', 'script3');
    }
}
