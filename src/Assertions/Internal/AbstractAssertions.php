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

namespace Playwright\Assertions\Internal;

use Playwright\Assertions\AssertionOptions;
use Playwright\Assertions\Failure\AssertionException;
use Playwright\Tracing\TracingInterface;

abstract class AbstractAssertions
{
    private bool $negated = false;
    private int $timeoutMs = Waiter::DEFAULT_TIMEOUT_MS;
    private int $pollIntervalMs = 50;

    public function __construct(private readonly ?TracingInterface $tracing = null)
    {
    }

    protected function negate(): void
    {
        $this->negated = !$this->negated;
    }

    protected function setTimeout(int $timeoutMs): void
    {
        $this->timeoutMs = $timeoutMs;
    }

    protected function setPollInterval(int $pollIntervalMs): void
    {
        $this->pollIntervalMs = $pollIntervalMs;
    }

    /**
     * @param callable(): bool       $condition
     * @param callable(): mixed|null $actualProvider
     */
    protected function assertCondition(
        callable $condition,
        string $matcher,
        ?AssertionOptions $options,
        string $message,
        string $negatedMessage,
        mixed $expected = null,
        ?callable $actualProvider = null,
    ): void {
        $negated = $this->negated;
        $this->negated = false;

        if (null !== $this->tracing) {
            $this->tracing->group(sprintf('expect(%s).%s%s', $this->subjectName(), $negated ? 'not.' : '', $matcher));
        }

        try {
            $this->runAssertion($condition, !$negated, $options, $negated ? $negatedMessage : $message, $expected, $actualProvider);
        } finally {
            if (null !== $this->tracing) {
                $this->tracing->groupEnd();
            }
        }
    }

    abstract protected function subjectName(): string;

    /**
     * @param callable(): bool       $condition
     * @param callable(): mixed|null $actualProvider
     */
    private function runAssertion(
        callable $condition,
        bool $expectedResult,
        ?AssertionOptions $options,
        string $message,
        mixed $expected,
        ?callable $actualProvider,
    ): void {
        $timeoutMs = null === $options || null === $options->timeoutMs ? $this->timeoutMs : $options->timeoutMs;
        $pollIntervalMs = null === $options || null === $options->intervalMs ? $this->pollIntervalMs : $options->intervalMs;
        $deadline = hrtime(true) + ($timeoutMs * 1_000_000);

        do {
            try {
                if ($condition() === $expectedResult) {
                    return;
                }
            } catch (\Throwable) {
            }

            if (hrtime(true) < $deadline) {
                usleep($pollIntervalMs * 1000);
            }
        } while (hrtime(true) < $deadline);

        $actual = null;
        if (null !== $actualProvider) {
            try {
                $actual = $actualProvider();
            } catch (\Throwable) {
            }
        }

        $message = null === $options || null === $options->message ? $message : $options->message;

        throw new AssertionException($message, actual: $actual, expected: $expected);
    }
}
