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

namespace Playwright\Assertions;

use Playwright\Assertions\Internal\AbstractAssertions;
use Playwright\Assertions\Internal\AriaSnapshot;
use Playwright\Assertions\Internal\ScreenshotExpectation;
use Playwright\Assertions\Options\ToHaveScreenshotOptions;
use Playwright\Page\Page;
use Playwright\Page\PageInterface;
use Playwright\Tracing\TracingInterface;

final class PageAssertions extends AbstractAssertions implements PageAssertionsInterface
{
    /**
     * @param string|null $screenshotOutputDir Where toHaveScreenshot() writes the expected, actual and diff images of a
     *                                         failure. Defaults to test-failures/snapshots/ in the working directory.
     * @param string|null $screenshotVariant   Suffix of the baseline names, such as "chromium-linux", left out of the
     *                                         failure image names: "card-chromium-linux.png" fails as "card-actual.png".
     */
    public function __construct(
        private readonly PageInterface $page,
        ?TracingInterface $tracing = null,
        private readonly ?string $screenshotOutputDir = null,
        private readonly ?string $screenshotVariant = null,
    ) {
        parent::__construct($tracing);
    }

    public function not(): self
    {
        $this->negate();

        return $this;
    }

    public function withTimeout(int $timeoutMs): self
    {
        $this->setTimeout($timeoutMs);

        return $this;
    }

    public function withPollInterval(int $pollIntervalMs): self
    {
        $this->setPollInterval($pollIntervalMs);

        return $this;
    }

    public function toHaveTitle(string|\Stringable $expected, ?AssertionOptions $options = null): self
    {
        $expected = (string) $expected;
        $this->assertCondition(
            fn (): bool => $this->page->title() === $expected,
            'toHaveTitle',
            $options,
            'Expected page title to match.',
            'Expected page title not to match.',
            $expected,
            fn (): string => $this->page->title(),
        );

        return $this;
    }

    public function toHaveURL(string|\Stringable $expected, ?AssertionOptions $options = null): self
    {
        $expected = (string) $expected;
        $this->assertCondition(
            fn (): bool => $this->page->url() === $expected,
            'toHaveURL',
            $options,
            'Expected page URL to match.',
            'Expected page URL not to match.',
            $expected,
            fn (): string => $this->page->url(),
        );

        return $this;
    }

    public function toMatchAriaSnapshot(string $expected, ?AssertionOptions $options = null): self
    {
        $normalized = AriaSnapshot::normalize($expected);

        $this->assertCondition(
            fn (): bool => AriaSnapshot::normalize($this->page->locator('body')->ariaSnapshot()) === $normalized,
            'toMatchAriaSnapshot',
            $options,
            'Expected page to match the ARIA snapshot.',
            'Expected page not to match the ARIA snapshot.',
        );

        return $this;
    }

    public function toHaveScreenshot(string $path, ?ToHaveScreenshotOptions $options = null): self
    {
        $options ??= new ToHaveScreenshotOptions();
        $subject = $this->page;
        if (!$subject instanceof Page) {
            throw new \LogicException(sprintf('toHaveScreenshot() needs a %s created by Playwright PHP, %s given.', Page::class, get_debug_type($subject)));
        }

        $outputDir = $this->screenshotOutputDir;
        $variant = $this->screenshotVariant;
        $this->assertWithoutPolling('toHaveScreenshot', $options->timeoutMs, static function (int $timeoutMs) use ($subject, $path, $options, $outputDir, $variant): void {
            $expectation = new ScreenshotExpectation(
                $subject->expectScreenshot(...),
                'page',
                outputDir: $outputDir,
                variant: $variant,
            );
            $expectation->assert($path, $options, $timeoutMs);
        });

        return $this;
    }

    protected function subjectName(): string
    {
        return 'page';
    }
}
