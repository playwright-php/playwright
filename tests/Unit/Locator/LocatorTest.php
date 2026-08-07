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
use Playwright\Exception\PlaywrightException;
use Playwright\Exception\ProtocolErrorException;
use Playwright\Exception\RuntimeException;
use Playwright\Exception\TimeoutException;
use Playwright\Frame\FrameLocatorInterface;
use Playwright\Locator\Locator;
use Playwright\Locator\Options\AriaSnapshotOptions;
use Playwright\Locator\Options\BoundingBoxOptions;
use Playwright\Locator\Options\DispatchEventOptions;
use Playwright\Locator\Options\SelectTextOptions;
use Playwright\Locator\Options\SetCheckedOptions;
use Playwright\Locator\Options\TapOptions;
use Playwright\Page\PageInterface;
use Playwright\Transport\TransportInterface;

#[CoversClass(Locator::class)]
final class LocatorTest extends TestCase
{
    private TransportInterface $transport;
    private Locator $locator;

    /**
     * Only set by tests that call createTmpDir(); '' means there is nothing to clean up.
     */
    private string $originalCwd = '';
    private string $tmpDir = '';

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->locator = new Locator($this->transport, 'page1', '.element');
    }

    protected function tearDown(): void
    {
        if ('' === $this->tmpDir) {
            return;
        }

        chdir($this->originalCwd);

        foreach (glob($this->tmpDir.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->tmpDir);
    }

    public function testToString(): void
    {
        $result = (string) $this->locator;
        $this->assertEquals('Locator(selector=".element")', $result);
    }

    public function testPressSequentiallySendsTextAndOptions(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $payload): bool {
                return 'locator.pressSequentially' === $payload['action']
                    && 'hello' === $payload['text']
                    && ['delay' => 10.0] === $payload['options'];
            }))
            ->willReturn([]);

        $this->locator->pressSequentially('hello', ['delay' => 10.0]);
    }

    public function testPressSendsKeyAndOptions(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $payload): bool {
                return 'locator.press' === $payload['action']
                    && 'Enter' === $payload['key']
                    && ['delay' => 10.0] === $payload['options'];
            }))
            ->willReturn([]);

        $this->locator->press('Enter', ['delay' => 10.0]);
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

    public function testAriaSnapshotSendsOptionsAndReturnsSnapshot(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $payload): bool {
                return 'locator.ariaSnapshot' === $payload['action']
                    && ['timeout' => 500.0] === $payload['options'];
            }))
            ->willReturn(['value' => '- button "Save"']);

        $this->assertSame('- button "Save"', $this->locator->ariaSnapshot(new AriaSnapshotOptions(timeout: 500.0)));
    }

    public function testBoundingBoxSendsOptionsAndReturnsCoordinates(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $payload): bool {
                return 'locator.boundingBox' === $payload['action']
                    && ['timeout' => 500.0] === $payload['options'];
            }))
            ->willReturn(['value' => ['x' => 1.0, 'y' => 2.0, 'width' => 3.0, 'height' => 4.0]]);

        $this->assertSame(
            ['x' => 1.0, 'y' => 2.0, 'width' => 3.0, 'height' => 4.0],
            $this->locator->boundingBox(new BoundingBoxOptions(timeout: 500.0))
        );
    }

    public function testDispatchEventSendsTypeInitializerAndOptions(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $payload): bool {
                return 'locator.dispatchEvent' === $payload['action']
                    && 'click' === $payload['type']
                    && ['detail' => 2] === $payload['eventInit']
                    && ['timeout' => 500.0] === $payload['options'];
            }))
            ->willReturn([]);

        $this->locator->dispatchEvent('click', ['detail' => 2], new DispatchEventOptions(timeout: 500.0));
    }

    public function testEvaluateAllSendsExpressionAndArgument(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $payload): bool {
                return 'locator.evaluateAll' === $payload['action']
                    && 'elements => elements.length + arg' === $payload['expression']
                    && 2 === $payload['arg'];
            }))
            ->willReturn(['value' => 4]);

        $this->assertSame(4, $this->locator->evaluateAll('elements => elements.length + arg', 2));
    }

    public function testHighlightSendsCommand(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static fn (array $payload): bool => 'locator.highlight' === $payload['action']))
            ->willReturn([]);

        $this->locator->highlight();
    }

    public function testSelectTextSendsOptions(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $payload): bool {
                return 'locator.selectText' === $payload['action']
                    && ['force' => true, 'timeout' => 500.0] === $payload['options'];
            }))
            ->willReturn([]);

        $this->locator->selectText(new SelectTextOptions(force: true, timeout: 500.0));
    }

    public function testSetCheckedSendsStateAndOptions(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $payload): bool {
                return 'locator.setChecked' === $payload['action']
                    && true === $payload['checked']
                    && ['force' => true, 'timeout' => 500.0] === $payload['options'];
            }))
            ->willReturn([]);

        $this->locator->setChecked(true, new SetCheckedOptions(force: true, timeout: 500.0));
    }

    public function testTapSendsOptions(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $payload): bool {
                return 'locator.tap' === $payload['action']
                    && ['modifiers' => ['Shift'], 'timeout' => 500.0] === $payload['options'];
            }))
            ->willReturn([]);

        $this->locator->tap(new TapOptions(modifiers: ['Shift'], timeout: 500.0));
    }

    public function testScrollIntoViewIfNeededSendsOptions(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $payload): bool {
                return 'locator.scrollIntoViewIfNeeded' === $payload['action']
                    && ['timeout' => 500.0] === $payload['options'];
            }))
            ->willReturn([]);

        $this->locator->scrollIntoViewIfNeeded(['timeout' => 500.0]);
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
            'Locator(selector=".element >> internal:role=button[name=/Submit/i][checked][pressed="mixed"][selected=false][include-hidden][level=2]")',
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

    public function testClickWaitsForActionable(): void
    {
        $callCount = 0;
        $this->transport
            ->expects($this->exactly(3))
            ->method('send')
            ->willReturnCallback(function ($payload) use (&$callCount) {
                ++$callCount;

                if (1 === $callCount) {
                    $this->assertEquals('locator.isVisible', $payload['action']);

                    return ['value' => true];
                }

                if (2 === $callCount) {
                    $this->assertEquals('locator.isEnabled', $payload['action']);

                    return ['value' => true];
                }

                if (3 === $callCount) {
                    $this->assertEquals('locator.click', $payload['action']);

                    return [];
                }

                return [];
            });

        $locator = new Locator($this->transport, 'page1', '.button');

        $locator->click();
    }

    public function testWaitForVisibleSucceeds(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.isVisible' === $payload['action'];
            }))
            ->willReturn(['value' => true]);

        $locator = new Locator($this->transport, 'page1', '.element');

        $locator->waitForVisible();
    }

    public function testWaitForVisibleTimeout(): void
    {
        $this->transport
            ->expects($this->atLeastOnce())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.isVisible' === $payload['action'];
            }))
            ->willReturn(['value' => false]);

        $locator = new Locator($this->transport, 'page1', '.element');

        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('Element not visible (timeout: 1000ms)');

        $locator->waitForVisible(['timeout' => 1000]);
    }

    public function testWaitForTextContains(): void
    {
        $callCount = 0;
        $this->transport
            ->expects($this->exactly(2))
            ->method('send')
            ->willReturnCallback(function ($payload) use (&$callCount) {
                ++$callCount;

                if ('locator.textContent' === $payload['action']) {
                    return ['value' => 1 === $callCount ? 'Loading...' : 'Success: Data loaded'];
                }

                return [];
            });

        $locator = new Locator($this->transport, 'page1', '.status');

        $locator->waitForText('Success');
    }

    public function testWaitForHidden(): void
    {
        $callCount = 0;
        $this->transport
            ->expects($this->exactly(2))
            ->method('send')
            ->willReturnCallback(function ($payload) use (&$callCount) {
                ++$callCount;

                if ('locator.isHidden' === $payload['action']) {
                    return ['value' => 2 === $callCount];
                }

                return [];
            });

        $locator = new Locator($this->transport, 'page1', '.modal');

        $locator->waitForHidden();
    }

    public function testFillWaitsForActionable(): void
    {
        $callCount = 0;
        $this->transport
            ->expects($this->exactly(3))
            ->method('send')
            ->willReturnCallback(function ($payload) use (&$callCount) {
                ++$callCount;

                if ($callCount <= 2) {
                    if ('locator.isVisible' === $payload['action']) {
                        return ['value' => true];
                    }
                    if ('locator.isEnabled' === $payload['action']) {
                        return ['value' => true];
                    }
                }

                if (3 === $callCount) {
                    $this->assertEquals('locator.fill', $payload['action']);
                    $this->assertEquals('test value', $payload['value']);

                    return [];
                }

                return [];
            });

        $locator = new Locator($this->transport, 'page1', 'input[type="text"]');

        $locator->fill('test value');
    }

    public function testWaitForAttached(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.isAttached' === $payload['action'];
            }))
            ->willReturn(['value' => true]);

        $locator = new Locator($this->transport, 'page1', '.dynamic-element');

        $locator->waitForAttached();
    }

    public function testWaitForDetached(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.isAttached' === $payload['action'];
            }))
            ->willReturn(['value' => false]);

        $locator = new Locator($this->transport, 'page1', '.removed-element');

        $locator->waitForDetached();
    }

    public function testFilterWithHasText(): void
    {
        $this->useItemsLocator();

        $filtered = $this->locator->filter(['hasText' => 'foo']);

        $this->assertInstanceOf(Locator::class, $filtered);
        $this->assertSame('Locator(selector=".items:has-text("foo")")', (string) $filtered);
    }

    public function testFilterWithHas(): void
    {
        $this->useItemsLocator();

        $inner = new Locator($this->transport, 'page1', '.inner');
        $filtered = $this->locator->filter(['has' => $inner]);

        $this->assertInstanceOf(Locator::class, $filtered);
        $this->assertSame('Locator(selector=".items:has(.inner)")', (string) $filtered);
    }

    public function testFilterWithHasNotText(): void
    {
        $this->useItemsLocator();

        $filtered = $this->locator->filter(['hasNotText' => 'bar']);

        $this->assertInstanceOf(Locator::class, $filtered);
        $this->assertSame('Locator(selector=".items:not(:has-text("bar"))")', (string) $filtered);
    }

    public function testFilterWithHasNot(): void
    {
        $this->useItemsLocator();

        $inner = new Locator($this->transport, 'page1', '.inner');
        $filtered = $this->locator->filter(['hasNot' => $inner]);

        $this->assertInstanceOf(Locator::class, $filtered);
        $this->assertSame('Locator(selector=".items:not(:has(.inner))")', (string) $filtered);
    }

    public function testAnd(): void
    {
        $this->useItemsLocator();

        $other = new Locator($this->transport, 'page1', '.active');
        $combined = $this->locator->and($other);

        $this->assertInstanceOf(Locator::class, $combined);
        $this->assertSame('Locator(selector=".items >> .active")', (string) $combined);
    }

    public function testOr(): void
    {
        $this->useItemsLocator();

        $other = new Locator($this->transport, 'page1', '.backup');
        $combined = $this->locator->or($other);

        $this->assertInstanceOf(Locator::class, $combined);
        $this->assertSame('Locator(selector=".items, .backup")', (string) $combined);
    }

    public function testDescribe(): void
    {
        $this->useItemsLocator();

        $described = $this->locator->describe('My custom locator');

        $this->assertInstanceOf(Locator::class, $described);
        $this->assertSame($this->locator, $described);
    }

    public function testContentFrame(): void
    {
        $this->useItemsLocator();

        $frameLocator = $this->locator->contentFrame();

        $this->assertInstanceOf(FrameLocatorInterface::class, $frameLocator);
    }

    public function testFilterWithEmptyOptions(): void
    {
        $this->useItemsLocator();

        $filtered = $this->locator->filter([]);

        $this->assertInstanceOf(Locator::class, $filtered);
        $this->assertSame('Locator(selector=".items")', (string) $filtered);
    }

    public function testFilterWithBothOptions(): void
    {
        $this->useItemsLocator();

        $inner = new Locator($this->transport, 'page1', '.inner');
        $filtered = $this->locator->filter([
            'hasText' => 'foo',
            'has' => $inner,
        ]);

        $this->assertInstanceOf(Locator::class, $filtered);
        $this->assertStringContainsString(':has-text("foo")', (string) $filtered);
        $this->assertStringContainsString(':has(.inner)', (string) $filtered);
    }

    public function testNormalizesReturnBodyToFunctionWithElement(): void
    {
        $transport = $this->createMock(TransportInterface::class);

        $transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.evaluate' === $payload['action']
                    && '(el, arg) => { return el.textContent; }' === $payload['expression'];
            }))
            ->willReturn(['value' => 'hello']);

        $locator = new Locator($transport, 'page1', '.title');
        $result = $locator->evaluate('return el.textContent;');
        $this->assertSame('hello', $result);
    }

    public function testLeavesPlainExpressionUntouched(): void
    {
        $transport = $this->createMock(TransportInterface::class);

        $transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.evaluate' === $payload['action']
                    && 'element.textContent' === $payload['expression'];
            }))
            ->willReturn(['value' => 'ok']);

        $locator = new Locator($transport, 'page1', '.title');
        $result = $locator->evaluate('element.textContent');
        $this->assertSame('ok', $result);
    }

    public function testRelativePathIsResolvedBeforeItReachesTheTransport(): void
    {
        $this->createTmpDir();

        file_put_contents($this->tmpDir.'/upload.txt', 'hello');
        chdir($this->tmpDir);

        $sent = null;
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')->willReturnCallback(function (array $payload) use (&$sent): array {
            $sent = $payload;

            return [];
        });

        (new Locator($transport, 'page1', '#f'))->setInputFiles('upload.txt');

        $this->assertSame([$this->tmpDir.'/upload.txt'], $sent['files']);
    }

    public function testMissingFileIsRejectedBeforeAnythingIsSent(): void
    {
        $this->createTmpDir();

        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->never())->method('send');

        $this->expectException(PlaywrightException::class);
        $this->expectExceptionMessage('File not found: nope.txt');

        (new Locator($transport, 'page1', '#f'))->setInputFiles('nope.txt');
    }

    public function testAriaSnapshotRejectsANonStringPayload(): void
    {
        $this->transport->method('send')->willReturn(['value' => ['not', 'a', 'string']]);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid ariaSnapshot response');

        $this->locator->ariaSnapshot();
    }

    public function testBoundingBoxIsNullForAnElementWithoutABox(): void
    {
        $this->transport->method('send')->willReturn(['value' => null]);

        $this->assertNull($this->locator->boundingBox());
    }

    public function testBoundingBoxRejectsAnIncompletePayload(): void
    {
        $this->transport->method('send')->willReturn(['value' => ['x' => 1.0, 'y' => 2.0]]);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid boundingBox response');

        $this->locator->boundingBox();
    }

    public function testBoundingBoxRejectsNonNumericCoordinates(): void
    {
        $this->transport->method('send')->willReturn([
            'value' => ['x' => 'left', 'y' => 2.0, 'width' => 3.0, 'height' => 4.0],
        ]);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid boundingBox response');

        $this->locator->boundingBox();
    }

    public function testPageReturnsTheOriginatingPage(): void
    {
        $page = $this->createMock(PageInterface::class);
        $locator = new Locator($this->transport, 'page1', '.element', null, null, [], $page);

        $this->assertSame($page, $locator->page());
    }

    public function testPageRejectsALocatorBuiltWithoutAPage(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This locator was not created from a page.');

        $this->locator->page();
    }

    public function testDerivedLocatorsCarryThePageAlong(): void
    {
        $page = $this->createMock(PageInterface::class);
        $locator = new Locator($this->transport, 'page1', '.items', null, null, [], $page);
        $other = new Locator($this->transport, 'page1', '.other');

        $this->assertSame($page, $locator->locator('.child')->page());
        $this->assertSame($page, $locator->nth(2)->page());
        $this->assertSame($page, $locator->filter(['hasText' => 'Save'])->page());
        $this->assertSame($page, $locator->and($other)->page());
        $this->assertSame($page, $locator->or($other)->page());
        $this->assertSame($page, $locator->frameLocator('iframe')->locator('.inner')->page());
        $this->assertSame($page, $locator->contentFrame()->locator('.inner')->page());
    }

    /**
     * Rebinds $this->locator to the '.items' selector used by the filter and
     * combinator tests, which need a different base selector than setUp().
     */
    private function useItemsLocator(): void
    {
        $this->locator = new Locator($this->transport, 'page1', '.items');
    }

    /**
     * Creates an empty working directory removed by tearDown().
     */
    private function createTmpDir(): void
    {
        $this->originalCwd = (string) getcwd();
        $this->tmpDir = sys_get_temp_dir().'/pw-input-files-'.bin2hex(random_bytes(6));
        mkdir($this->tmpDir);
        $this->tmpDir = (string) realpath($this->tmpDir);
    }
}
