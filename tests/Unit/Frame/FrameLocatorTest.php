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

namespace Playwright\Tests\Unit\Frame;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Playwright\Frame\FrameLocator;
use Playwright\Locator\Locator;
use Playwright\Locator\LocatorInterface;
use Playwright\Transport\TransportInterface;
use Psr\Log\LoggerInterface;

/**
 * To make these tests runnable, you would need to define the following
 * classes and interfaces, or ensure they are available via your autoloader.
 * The FrameLocator would contain the code you provided.
 *
 * namespace Vendor\Library;
 *
 * interface TransportInterface {}
 * interface LocatorInterface {
 *     public function getSelector(): string;
 * }
 * class Locator implements LocatorInterface {
 *     private string $selector;
 *     public function __construct(TransportInterface $transport, string $pageId, string $selector, ?array $options, LoggerInterface $logger) { $this->selector = $selector; }
 *     public function getSelector(): string { return $this->selector; }
 * }
 */
#[CoversClass(FrameLocator::class)]
class FrameLocatorTest extends TestCase
{
    private MockObject|TransportInterface $transport;
    private MockObject|LoggerInterface $logger;
    private string $pageId = 'page-id-42';
    private string $initialSelector = 'iframe[name="main"]';
    private FrameLocator $frameLocator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transport = $this->createMock(TransportInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->frameLocator = new FrameLocator(
            $this->transport,
            $this->pageId,
            $this->initialSelector,
            $this->logger
        );
    }

    public function testFirst(): void
    {
        $expectedSelector = $this->initialSelector.' >> nth=0';
        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with('Creating nth frame locator', [
                'frameSelector' => $this->initialSelector,
                'index' => 0,
                'newSelector' => $expectedSelector,
            ]);

        $newLocator = $this->frameLocator->first();

        $this->assertInstanceOf(FrameLocator::class, $newLocator);
        $this->assertNotSame($this->frameLocator, $newLocator, 'A new instance should be returned.');
        $this->assertSame($expectedSelector, $newLocator->getSelector());
    }

    public function testLast(): void
    {
        $expectedSelector = $this->initialSelector.' >> nth=-1';
        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with('Creating nth frame locator', [
                'frameSelector' => $this->initialSelector,
                'index' => -1,
                'newSelector' => $expectedSelector,
            ]);

        $newLocator = $this->frameLocator->last();

        $this->assertInstanceOf(FrameLocator::class, $newLocator);
        $this->assertNotSame($this->frameLocator, $newLocator, 'A new instance should be returned.');
        $this->assertSame($expectedSelector, $newLocator->getSelector());
    }

    #[DataProvider('nthDataProvider')]
    public function testNth(int $index, string $expectedSelector): void
    {
        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with('Creating nth frame locator', [
                'frameSelector' => $this->initialSelector,
                'index' => $index,
                'newSelector' => $expectedSelector,
            ]);

        $newLocator = $this->frameLocator->nth($index);

        $this->assertInstanceOf(FrameLocator::class, $newLocator);
        $this->assertNotSame($this->frameLocator, $newLocator, 'A new instance should be returned.');
        $this->assertSame($expectedSelector, $newLocator->getSelector());
    }

    public static function nthDataProvider(): array
    {
        $initialSelector = 'iframe[name="main"]';

        return [
            'zero index' => [0, $initialSelector.' >> nth=0'],
            'positive index' => [5, $initialSelector.' >> nth=5'],
            'negative index' => [-3, $initialSelector.' >> nth=-3'],
        ];
    }

    public function testFrameLocator(): void
    {
        $childSelector = '#child-frame';
        $expectedSelector = $this->initialSelector.' >> '.$childSelector;

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with('Creating nested frame locator', [
                'parentFrameSelector' => $this->initialSelector,
                'childSelector' => $childSelector,
                'newSelector' => $expectedSelector,
            ]);

        $newLocator = $this->frameLocator->frameLocator($childSelector);

        $this->assertInstanceOf(FrameLocator::class, $newLocator);
        $this->assertNotSame($this->frameLocator, $newLocator, 'A new instance should be returned.');
        $this->assertSame($expectedSelector, $newLocator->getSelector());
    }

    public function testGetSelector(): void
    {
        $this->assertSame($this->initialSelector, $this->frameLocator->getSelector());
    }

    public function testToString(): void
    {
        $expectedString = 'FrameLocator(selector="'.$this->initialSelector.'")';
        $this->assertSame($expectedString, (string) $this->frameLocator);
        $this->assertSame($expectedString, $this->frameLocator->__toString());
    }

    public function testGetByText(): void
    {
        $locator = $this->frameLocator->getByText('Hello');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $this->assertSame('text="Hello"', $locator->getSelector());
    }

    public function testGetByRole(): void
    {
        $locator = $this->frameLocator->getByRole('button');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $this->assertSame('internal:role=button', $locator->getSelector());
    }

    public function testGetByPlaceholder(): void
    {
        $locator = $this->frameLocator->getByPlaceholder('Search');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $this->assertSame('[placeholder="Search"]', $locator->getSelector());
    }

    public function testGetByTestId(): void
    {
        $locator = $this->frameLocator->getByTestId('my-test-id');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $this->assertSame('[data-testid="my-test-id"]', $locator->getSelector());
    }

    public function testGetByAltText(): void
    {
        $locator = $this->frameLocator->getByAltText('Image');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $this->assertSame('[alt="Image"]', $locator->getSelector());
    }

    public function testGetByTitle(): void
    {
        $locator = $this->frameLocator->getByTitle('Help');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $this->assertSame('[title="Help"]', $locator->getSelector());
    }

    public function testGetByLabel(): void
    {
        $locator = $this->frameLocator->getByLabel('Password');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $this->assertSame('label:text-is("Password") >> nth=0', $locator->getSelector());
    }
}
