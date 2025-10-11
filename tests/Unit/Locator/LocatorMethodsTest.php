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
use Playwright\Frame\FrameLocatorInterface;
use Playwright\Locator\Locator;
use Playwright\Transport\TransportInterface;

#[CoversClass(Locator::class)]
final class LocatorMethodsTest extends TestCase
{
    private TransportInterface $transport;
    private Locator $locator;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->locator = new Locator($this->transport, 'page1', '.element');
    }

    public function testToString(): void
    {
        $result = (string) $this->locator;
        $this->assertEquals('Locator(selector=".element")', $result);
    }

    public function testDblclick(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.dblclick' === $payload['action']
                    && 'left' === $payload['options']['button'];
            }))
            ->willReturn([]);

        $this->locator->dblclick(['button' => 'left']);
    }

    public function testClear(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.clear' === $payload['action'];
            }))
            ->willReturn([]);

        $this->locator->clear();
    }

    public function testFocus(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.focus' === $payload['action'];
            }))
            ->willReturn([]);

        $this->locator->focus();
    }

    public function testBlur(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.blur' === $payload['action'];
            }))
            ->willReturn([]);

        $this->locator->blur();
    }

    public function testScreenshotWithPath(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.screenshot' === $payload['action']
                    && '/tmp/test.png' === $payload['options']['path'];
            }))
            ->willReturn([]);

        $result = $this->locator->screenshot('/tmp/test.png');
        $this->assertNull($result);
    }

    public function testScreenshotWithoutPath(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.screenshot' === $payload['action']
                    && !isset($payload['options']['path']);
            }))
            ->willReturn(['binary' => 'base64data']);

        $result = $this->locator->screenshot();
        $this->assertEquals('base64data', $result);
    }

    public function testAllInnerTexts(): void
    {
        $expected = ['Text 1', 'Text 2', 'Text 3'];

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.allInnerTexts' === $payload['action'];
            }))
            ->willReturn(['value' => $expected]);

        $result = $this->locator->allInnerTexts();
        $this->assertEquals($expected, $result);
    }

    public function testAllTextContents(): void
    {
        $expected = ['Content 1', 'Content 2'];

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn(['value' => $expected]);

        $result = $this->locator->allTextContents();
        $this->assertEquals($expected, $result);
    }

    public function testInnerHTML(): void
    {
        $expected = '<div>Hello</div>';

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn(['value' => $expected]);

        $result = $this->locator->innerHTML();
        $this->assertEquals($expected, $result);
    }

    public function testInnerText(): void
    {
        $expected = 'Hello World';

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn(['value' => $expected]);

        $result = $this->locator->innerText();
        $this->assertEquals($expected, $result);
    }

    public function testInputValue(): void
    {
        $expected = 'input value';

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn(['value' => $expected]);

        $result = $this->locator->inputValue();
        $this->assertEquals($expected, $result);
    }

    public function testBooleanMethods(): void
    {
        $methods = [
            'isAttached' => true,
            'isChecked' => false,
            'isDisabled' => true,
            'isEditable' => false,
            'isEmpty' => true,
            'isEnabled' => false,
            'isHidden' => true,
            'isVisible' => false,
        ];

        foreach ($methods as $method => $expectedValue) {
            $this->transport
                ->expects($this->once())
                ->method('send')
                ->with($this->callback(function ($payload) use ($method) {
                    return $payload['action'] === "locator.$method";
                }))
                ->willReturn(['value' => $expectedValue]);

            $result = $this->locator->$method();
            $this->assertEquals($expectedValue, $result, "Method $method failed");

            $this->transport = $this->createMock(TransportInterface::class);
            $this->locator = new Locator($this->transport, 'page1', '.element');
        }
    }

    public function testLocator(): void
    {
        $childLocator = $this->locator->locator('.child');

        $this->assertInstanceOf(Locator::class, $childLocator);
        $this->assertEquals('Locator(selector=".element >> .child")', (string) $childLocator);
    }

    public function testNth(): void
    {
        $nthLocator = $this->locator->nth(2);

        $this->assertInstanceOf(Locator::class, $nthLocator);
        $this->assertEquals('Locator(selector=".element >> nth=2")', (string) $nthLocator);
    }

    public function testFirst(): void
    {
        $firstLocator = $this->locator->first();

        $this->assertInstanceOf(Locator::class, $firstLocator);
        $this->assertEquals('Locator(selector=".element >> nth=0")', (string) $firstLocator);
    }

    public function testLast(): void
    {
        $lastLocator = $this->locator->last();

        $this->assertInstanceOf(Locator::class, $lastLocator);
        $this->assertEquals('Locator(selector=".element >> nth=-1")', (string) $lastLocator);
    }

    public function testAll(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.count' === $payload['action'];
            }))
            ->willReturn(['value' => 3]);

        $allLocators = $this->locator->all();

        $this->assertCount(3, $allLocators);
        $this->assertContainsOnlyInstancesOf(Locator::class, $allLocators);
        $this->assertEquals('Locator(selector=".element >> nth=0")', (string) $allLocators[0]);
        $this->assertEquals('Locator(selector=".element >> nth=1")', (string) $allLocators[1]);
        $this->assertEquals('Locator(selector=".element >> nth=2")', (string) $allLocators[2]);
    }

    public function testFrameLocator(): void
    {
        $frameLocator = $this->locator->frameLocator('iframe');

        $this->assertInstanceOf(FrameLocatorInterface::class, $frameLocator);
    }

    public function testEvaluate(): void
    {
        $expected = 'evaluated result';

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.evaluate' === $payload['action']
                    && 'element.textContent' === $payload['expression']
                    && 'test-arg' === $payload['arg'];
            }))
            ->willReturn(['value' => $expected]);

        $result = $this->locator->evaluate('element.textContent', 'test-arg');
        $this->assertEquals($expected, $result);
    }

    public function testEvaluateWithNullResult(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn([]);

        $result = $this->locator->evaluate('element.nonexistent');
        $this->assertNull($result);
    }

    public function testDragToBasic(): void
    {
        $targetLocator = new Locator($this->transport, 'page1', '.target');

        $this->transport
            ->expects($this->exactly(3))
            ->method('send')
            ->willReturnCallback(function ($payload) {
                // First call: isVisible check
                if ('locator.isVisible' === $payload['action']) {
                    return ['value' => true];
                }
                // Second call: isEnabled check
                if ('locator.isEnabled' === $payload['action']) {
                    return ['value' => true];
                }
                // Third call: actual dragAndDrop
                if ('locator.dragAndDrop' === $payload['action']
                    && '.target' === $payload['target']
                    && [] === $payload['options']) {
                    return ['value' => true];
                }

                return [];
            });

        $this->transport
            ->expects($this->once())
            ->method('processEvents');

        $this->locator->dragTo($targetLocator);
    }

    public function testDragToWithOptions(): void
    {
        $targetLocator = new Locator($this->transport, 'page1', '.target');
        $options = [
            'sourcePosition' => ['x' => 10, 'y' => 15],
            'targetPosition' => ['x' => 20, 'y' => 25],
            'force' => true,
            'timeout' => 5000,
        ];

        $this->transport
            ->expects($this->exactly(3))
            ->method('send')
            ->willReturnCallback(function ($payload) use ($options) {
                // First call: isVisible check
                if ('locator.isVisible' === $payload['action']) {
                    return ['value' => true];
                }
                // Second call: isEnabled check
                if ('locator.isEnabled' === $payload['action']) {
                    return ['value' => true];
                }
                // Third call: actual dragAndDrop
                if ('locator.dragAndDrop' === $payload['action']
                    && '.target' === $payload['target']
                    && $options === $payload['options']) {
                    return ['value' => true];
                }

                return [];
            });

        $this->transport
            ->expects($this->once())
            ->method('processEvents');

        $this->locator->dragTo($targetLocator, $options);
    }

    public function testDragToWithComplexSelectors(): void
    {
        $targetLocator = new Locator($this->transport, 'page1', '#dropzone .drop-target[data-accept="files"]');

        $this->transport
            ->expects($this->exactly(3))
            ->method('send')
            ->willReturnCallback(function ($payload) {
                // First call: isVisible check
                if ('locator.isVisible' === $payload['action']) {
                    return ['value' => true];
                }
                // Second call: isEnabled check
                if ('locator.isEnabled' === $payload['action']) {
                    return ['value' => true];
                }
                // Third call: actual dragAndDrop
                if ('locator.dragAndDrop' === $payload['action']
                    && '#dropzone .drop-target[data-accept="files"]' === $payload['target']) {
                    return ['value' => true];
                }

                return [];
            });

        $this->transport
            ->expects($this->once())
            ->method('processEvents');

        $this->locator->dragTo($targetLocator);
    }

    public function testGetByText(): void
    {
        $result = $this->locator->getByText('Hello');
        $this->assertInstanceOf(Locator::class, $result);
        $this->assertSame('Locator(selector=".element >> text="Hello"")', (string) $result);
    }

    public function testGetByRole(): void
    {
        $result = $this->locator->getByRole('button');
        $this->assertInstanceOf(Locator::class, $result);
        $this->assertSame('Locator(selector=".element >> internal:role=button")', (string) $result);
    }

    public function testGetByRoleWithOptions(): void
    {
        $result = $this->locator->getByRole('button', [
            'name' => 'Submit',
            'checked' => true,
            'includeHidden' => true,
            'pressed' => 'mixed',
            'selected' => false,
            'level' => 2,
        ]);

        $this->assertSame(
            'Locator(selector=".element >> internal:role=button[name="Submit"][checked][pressed="mixed"][selected=false][include-hidden][level=2]")',
            (string) $result
        );
    }

    public function testGetByPlaceholder(): void
    {
        $result = $this->locator->getByPlaceholder('Search');
        $this->assertInstanceOf(Locator::class, $result);
        $this->assertSame('Locator(selector=".element >> [placeholder="Search"]")', (string) $result);
    }

    public function testGetByTestId(): void
    {
        $result = $this->locator->getByTestId('submit-btn');
        $this->assertInstanceOf(Locator::class, $result);
        $this->assertSame('Locator(selector=".element >> [data-testid="submit-btn"]")', (string) $result);
    }

    public function testGetByAltText(): void
    {
        $result = $this->locator->getByAltText('Logo');
        $this->assertInstanceOf(Locator::class, $result);
        $this->assertSame('Locator(selector=".element >> [alt="Logo"]")', (string) $result);
    }

    public function testGetByTitle(): void
    {
        $result = $this->locator->getByTitle('Help');
        $this->assertInstanceOf(Locator::class, $result);
        $this->assertSame('Locator(selector=".element >> [title="Help"]")', (string) $result);
    }

    public function testGetByLabel(): void
    {
        $result = $this->locator->getByLabel('Email');
        $this->assertInstanceOf(Locator::class, $result);
        $this->assertSame('Locator(selector=".element >> label:text-is("Email") >> nth=0")', (string) $result);
    }
}
