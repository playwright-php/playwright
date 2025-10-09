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

use Playwright\Assertions\Failure\AssertionException;

final class GenericAssertions implements GenericAssertionsInterface
{
    private bool $negated = false;

    public function __construct(private mixed $subject)
    {
    }

    public function not(): self
    {
        $this->negated = !$this->negated;

        return $this;
    }

    public function toEqual(mixed $expected, ?AssertionOptions $options = null): self
    {
        $ok = $this->subject == $expected;
        if ($this->negated) {
            $ok = !$ok;
            $this->negated = false;
        }

        if (!$ok) {
            $msg = $options?->message;
            if (null === $msg) {
                $msg = 'Expected values to be equal.';
            }
            throw new AssertionException($msg, actual: $this->subject, expected: $expected);
        }

        return $this;
    }

    public function toBeTruthy(?AssertionOptions $options = null): self
    {
        $ok = (bool) $this->subject;
        if ($this->negated) {
            $ok = !$ok;
            $this->negated = false;
        }
        if (!$ok) {
            $msg = $options?->message;
            if (null === $msg) {
                $msg = 'Expected value to be truthy.';
            }
            throw new AssertionException($msg, actual: $this->subject);
        }

        return $this;
    }

    public function toBeFalsy(?AssertionOptions $options = null): self
    {
        return $this->not()->toBeTruthy($options);
    }

    public function toBeGreaterThan(int|float $expected, ?AssertionOptions $options = null): self
    {
        $ok = is_numeric($this->subject) && $this->subject > $expected;
        if ($this->negated) {
            $ok = !$ok;
            $this->negated = false;
        }
        if (!$ok) {
            $msg = $options?->message;
            if (null === $msg) {
                $msg = 'Expected value to be greater.';
            }
            throw new AssertionException($msg, actual: $this->subject, expected: $expected);
        }

        return $this;
    }
}
