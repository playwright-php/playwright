<?php

namespace PlaywrightPHP\Tests\Unit\Frame;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\FrameLocator\FrameLocator;
use PlaywrightPHP\Locator\Locator;
use PlaywrightPHP\Locator\LocatorInterface;
use PlaywrightPHP\Transport\TransportInterface;
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
        $expectedSelector = $this->initialSelector . ' >> nth=0';
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
        $expectedSelector = $this->initialSelector . ' >> nth=-1';
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
            'zero index' => [0, $initialSelector . ' >> nth=0'],
            'positive index' => [5, $initialSelector . ' >> nth=5'],
            'negative index' => [-3, $initialSelector . ' >> nth=-3'],
        ];
    }

    public function testFrameLocator(): void
    {
        $childSelector = '#child-frame';
        $expectedSelector = $this->initialSelector . ' >> ' . $childSelector;

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
        $expectedString = 'FrameLocator(selector="' . $this->initialSelector . '")';
        $this->assertSame($expectedString, (string) $this->frameLocator);
        $this->assertSame($expectedString, $this->frameLocator->__toString());
    }
}
