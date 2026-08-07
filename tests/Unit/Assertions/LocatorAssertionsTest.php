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

namespace Playwright\Tests\Unit\Assertions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Assertions\AssertionOptions;
use Playwright\Assertions\Failure\AssertionException;
use Playwright\Assertions\LocatorAssertions;
use Playwright\Locator\LocatorInterface;

#[CoversClass(LocatorAssertions::class)]
final class LocatorAssertionsTest extends TestCase
{
    public function testToBeAttached(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())->method('isAttached')->willReturn(true);

        $assertions = new LocatorAssertions($locator);

        $this->assertSame($assertions, $assertions->toBeAttached());
    }

    public function testModifiersAreFluent(): void
    {
        $assertions = new LocatorAssertions($this->createMock(LocatorInterface::class));

        $this->assertSame($assertions, $assertions->withTimeout(100));
        $this->assertSame($assertions, $assertions->withPollInterval(10));
    }

    public function testToBeAttachedRetriesAfterAnEvaluationError(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $calls = 0;
        $locator->expects($this->exactly(2))
            ->method('isAttached')
            ->willReturnCallback(static function () use (&$calls): bool {
                if (1 === ++$calls) {
                    throw new \RuntimeException('Detached during evaluation');
                }

                return true;
            });

        $this->assertInstanceOf(
            LocatorAssertions::class,
            (new LocatorAssertions($locator))->withPollInterval(0)->toBeAttached(),
        );
    }

    public function testToBeEditable(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())->method('isEditable')->willReturn(true);

        $assertions = new LocatorAssertions($locator);

        $this->assertSame($assertions, $assertions->toBeEditable());
    }

    public function testToBeAttachedResetsNegationAfterFailure(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->exactly(2))->method('isAttached')->willReturn(true);
        $assertions = new LocatorAssertions($locator);

        try {
            $assertions->not()->toBeAttached(new AssertionOptions(timeoutMs: 0));
            $this->fail('Expected the negated assertion to fail.');
        } catch (AssertionException $exception) {
            $this->assertSame('Expected locator to be detached.', $exception->getMessage());
        }

        $this->assertSame($assertions, $assertions->toBeAttached());
    }

    public function testToBeEditableResetsNegationAfterFailure(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->exactly(2))->method('isEditable')->willReturn(true);
        $assertions = new LocatorAssertions($locator);

        try {
            $assertions->not()->toBeEditable(new AssertionOptions(timeoutMs: 0));
            $this->fail('Expected the negated assertion to fail.');
        } catch (AssertionException $exception) {
            $this->assertSame('Expected locator not to be editable.', $exception->getMessage());
        }

        $this->assertSame($assertions, $assertions->toBeEditable());
    }

    public function testToBeInViewportUsesConfiguredRatio(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('evaluate')
            ->with($this->isType('string'), 0.5)
            ->willReturn(true);

        $this->assertInstanceOf(LocatorAssertions::class, (new LocatorAssertions($locator))->toBeInViewport(new AssertionOptions(ratio: 0.5)));
    }

    public function testToBeInViewportRejectsAnInvalidRatio(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new LocatorAssertions($this->createMock(LocatorInterface::class)))->toBeInViewport(new AssertionOptions(ratio: 1.1));
    }

    public function testToHaveTextMatchesExactlyInTheCanonicalApi(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())->method('textContent')->willReturn('Exact text');

        $this->assertInstanceOf(LocatorAssertions::class, (new LocatorAssertions($locator))->toHaveText('Exact text'));
    }

    public function testToHaveTextSupportsMultipleInnerTexts(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())->method('allInnerTexts')->willReturn(['First', 'Second']);

        $this->assertInstanceOf(
            LocatorAssertions::class,
            (new LocatorAssertions($locator))->toHaveText(
                ['First', 'Second'],
                new AssertionOptions(useInnerText: true),
            ),
        );
    }

    public function testFailureStillThrowsWhenReadingTheActualValueFails(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $calls = 0;
        $locator->expects($this->exactly(2))
            ->method('count')
            ->willReturnCallback(static function () use (&$calls): int {
                if (1 === ++$calls) {
                    return 1;
                }

                throw new \RuntimeException('Gone');
            });

        $this->expectException(AssertionException::class);

        (new LocatorAssertions($locator))->toHaveCount(2, new AssertionOptions(timeoutMs: 0));
    }

    public function testToHaveJavaScriptPropertyUsesDeepNativeComparison(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('evaluate')
            ->with($this->isType('string'), ['name' => 'state', 'expected' => ['ready' => true]])
            ->willReturn(true);

        $this->assertInstanceOf(LocatorAssertions::class, (new LocatorAssertions($locator))->toHaveJSProperty('state', ['ready' => true]));
    }

    public function testToHaveValuesNormalizesASingleExpectedValue(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('evaluate')
            ->with($this->isType('string'))
            ->willReturn(['second']);

        $this->assertInstanceOf(LocatorAssertions::class, (new LocatorAssertions($locator))->toHaveValues('second'));
    }

    public function testToHaveRoleRespectsNegationAndTimeout(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('evaluate')
            ->with($this->isType('string'))
            ->willReturn('button');

        $this->assertInstanceOf(
            LocatorAssertions::class,
            (new LocatorAssertions($locator))->not()->toHaveRole('link', new AssertionOptions(timeoutMs: 0))
        );
    }

    public function testToContainClassEvaluatesEachClassToken(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('evaluate')
            ->with($this->isType('string'), ['primary', 'active'])
            ->willReturn(true);

        $this->assertInstanceOf(LocatorAssertions::class, (new LocatorAssertions($locator))->toContainClass('primary active'));
    }

    public function testToContainClassRejectsAnEmptyClassList(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new LocatorAssertions($this->createMock(LocatorInterface::class)))->toContainClass('   ');
    }

    public function testToHaveAccessibleNameSendsTheExpectationToTheBrowser(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('evaluate')
            ->with($this->isType('string'), ['kind' => 'name', 'expected' => 'Full name', 'ignoreCase' => false])
            ->willReturn(true);

        $this->assertInstanceOf(LocatorAssertions::class, (new LocatorAssertions($locator))->toHaveAccessibleName('Full name'));
    }

    public function testToHaveAccessibleNameForwardsTheIgnoreCaseOption(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('evaluate')
            ->with($this->isType('string'), ['kind' => 'name', 'expected' => 'FULL NAME', 'ignoreCase' => true])
            ->willReturn(true);

        $this->assertInstanceOf(
            LocatorAssertions::class,
            (new LocatorAssertions($locator))->toHaveAccessibleName('FULL NAME', new AssertionOptions(ignoreCase: true))
        );
    }

    public function testToHaveAccessibleNameFailsWithADescriptiveMessage(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->method('evaluate')->willReturn(false);

        $this->expectException(AssertionException::class);
        $this->expectExceptionMessage('Expected locator to have accessible name "Full name".');

        (new LocatorAssertions($locator))->toHaveAccessibleName('Full name', new AssertionOptions(timeoutMs: 0));
    }

    public function testToHaveAccessibleDescriptionSelectsTheDescriptionResolver(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('evaluate')
            ->with($this->isType('string'), ['kind' => 'description', 'expected' => 'Hint', 'ignoreCase' => false])
            ->willReturn(true);

        $this->assertInstanceOf(LocatorAssertions::class, (new LocatorAssertions($locator))->toHaveAccessibleDescription('Hint'));
    }

    public function testToHaveAccessibleDescriptionFailsWithADescriptiveMessage(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->method('evaluate')->willReturn(false);

        $this->expectException(AssertionException::class);
        $this->expectExceptionMessage('Expected locator to have accessible description "Hint".');

        (new LocatorAssertions($locator))->toHaveAccessibleDescription('Hint', new AssertionOptions(timeoutMs: 0));
    }

    public function testToHaveAccessibleErrorMessageSelectsTheErrorMessageResolver(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('evaluate')
            ->with($this->isType('string'), ['kind' => 'errorMessage', 'expected' => 'Required', 'ignoreCase' => false])
            ->willReturn(true);

        $this->assertInstanceOf(LocatorAssertions::class, (new LocatorAssertions($locator))->toHaveAccessibleErrorMessage('Required'));
    }

    public function testToHaveAccessibleErrorMessageIsNegatable(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())->method('evaluate')->willReturn(false);

        $this->assertInstanceOf(
            LocatorAssertions::class,
            (new LocatorAssertions($locator))->not()->toHaveAccessibleErrorMessage('Required', new AssertionOptions(timeoutMs: 0))
        );
    }

    public function testToMatchAriaSnapshotIgnoresIndentationAndBlankLines(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())->method('ariaSnapshot')->willReturn("- list:\n  - listitem: One\n");

        $expected = <<<'YAML'

                - list:
                  - listitem: One

            YAML;

        $this->assertInstanceOf(LocatorAssertions::class, (new LocatorAssertions($locator))->toMatchAriaSnapshot($expected));
    }

    public function testToMatchAriaSnapshotFailsOnADifferentTree(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->method('ariaSnapshot')->willReturn('- list:');

        $this->expectException(AssertionException::class);
        $this->expectExceptionMessage('Expected locator to match the ARIA snapshot.');

        (new LocatorAssertions($locator))->toMatchAriaSnapshot('- heading "One"', new AssertionOptions(timeoutMs: 0));
    }

    public function testToMatchAriaSnapshotIsNegatable(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())->method('ariaSnapshot')->willReturn('- list:');

        $this->assertInstanceOf(
            LocatorAssertions::class,
            (new LocatorAssertions($locator))->not()->toMatchAriaSnapshot('- heading "One"', new AssertionOptions(timeoutMs: 0))
        );
    }

    public function testToBeInViewportDefaultsToAnyVisiblePart(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('evaluate')
            ->with($this->isType('string'), 0.0)
            ->willReturn(true);

        $assertions = new LocatorAssertions($locator);

        $this->assertSame($assertions, $assertions->toBeInViewport());
    }

    public function testToBeInViewportRejectsARatioAboveOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Viewport ratio must be between 0 and 1.');

        (new LocatorAssertions($this->createMock(LocatorInterface::class)))
            ->toBeInViewport(new AssertionOptions(ratio: 1.5));
    }

    public function testToBeInViewportRejectsANegativeRatio(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Viewport ratio must be between 0 and 1.');

        (new LocatorAssertions($this->createMock(LocatorInterface::class)))
            ->toBeInViewport(new AssertionOptions(ratio: -0.1));
    }
}
