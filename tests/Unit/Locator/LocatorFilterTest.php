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

namespace Playwright\Tests\Unit\Locator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Locator\Locator;
use Playwright\Transport\TransportInterface;

#[CoversClass(Locator::class)]
final class LocatorFilterTest extends TestCase
{
    private TransportInterface $transport;
    private Locator $locator;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->locator = new Locator($this->transport, 'page1', '.items');
    }

    public function testFilterWithHasText(): void
    {
        $filtered = $this->locator->filter(['hasText' => 'foo']);

        $this->assertInstanceOf(Locator::class, $filtered);
        $this->assertSame('Locator(selector=".items >> :has-text("foo")")', (string) $filtered);
    }

    public function testFilterWithHas(): void
    {
        $inner = new Locator($this->transport, 'page1', '.inner');
        $filtered = $this->locator->filter(['has' => $inner]);

        $this->assertInstanceOf(Locator::class, $filtered);
        $this->assertSame('Locator(selector=".items >> :has(.inner)")', (string) $filtered);
    }

    public function testAnd(): void
    {
        $other = new Locator($this->transport, 'page1', '.active');
        $combined = $this->locator->and($other);

        $this->assertInstanceOf(Locator::class, $combined);
        $this->assertSame('Locator(selector=".items >> .active")', (string) $combined);
    }

    public function testOr(): void
    {
        $other = new Locator($this->transport, 'page1', '.backup');
        $combined = $this->locator->or($other);

        $this->assertInstanceOf(Locator::class, $combined);
        $this->assertSame('Locator(selector=".items, .backup")', (string) $combined);
    }

    public function testDescribe(): void
    {
        $described = $this->locator->describe('My custom locator');

        $this->assertInstanceOf(Locator::class, $described);
        $this->assertSame($this->locator, $described);
    }

    public function testContentFrame(): void
    {
        $frameLocator = $this->locator->contentFrame();

        $this->assertInstanceOf(\Playwright\Frame\FrameLocatorInterface::class, $frameLocator);
    }

    public function testFilterWithEmptyOptions(): void
    {
        $filtered = $this->locator->filter([]);

        $this->assertInstanceOf(Locator::class, $filtered);
        $this->assertSame('Locator(selector=".items")', (string) $filtered);
    }

    public function testFilterWithBothOptions(): void
    {
        $inner = new Locator($this->transport, 'page1', '.inner');
        $filtered = $this->locator->filter([
            'hasText' => 'foo',
            'has' => $inner,
        ]);

        $this->assertInstanceOf(Locator::class, $filtered);
        $this->assertStringContainsString(':has-text("foo")', (string) $filtered);
        $this->assertStringContainsString(':has(.inner)', (string) $filtered);
    }

    public function testFilterWithNonLocatorHas(): void
    {
        $filtered = $this->locator->filter(['has' => 'not-a-locator']);

        $this->assertInstanceOf(Locator::class, $filtered);
    }
}
