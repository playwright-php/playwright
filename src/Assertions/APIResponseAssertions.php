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

use Playwright\API\APIResponseInterface;
use Playwright\Assertions\Failure\AssertionException;

final class APIResponseAssertions implements APIResponseAssertionsInterface
{
    private bool $negated = false;

    public function __construct(private APIResponseInterface $response)
    {
    }

    public function not(): self
    {
        $this->negated = !$this->negated;

        return $this;
    }

    public function toBeOK(?AssertionOptions $options = null): self
    {
        $ok = $this->response->ok();
        if ($this->negated) {
            $ok = !$ok;
            $this->negated = false;
        }
        if (!$ok) {
            $msg = $options?->message;
            if (null === $msg) {
                $msg = 'Expected response to be OK (2xx).';
            }
            throw new AssertionException($msg, actual: $this->response->status(), expected: '2xx');
        }

        return $this;
    }

    public function toHaveStatus(int $status, ?AssertionOptions $options = null): self
    {
        $ok = $this->response->status() === $status;
        if ($this->negated) {
            $ok = !$ok;
            $this->negated = false;
        }
        if (!$ok) {
            throw new AssertionException('Expected response status to match.', actual: $this->response->status(), expected: $status);
        }

        return $this;
    }

    public function toHaveJSON(mixed $expected, ?AssertionOptions $options = null): self
    {
        $actual = $this->response->json();
        $ok = $actual == $expected;
        if ($this->negated) {
            $ok = !$ok;
            $this->negated = false;
        }
        if (!$ok) {
            throw new AssertionException('Expected JSON body to match.', actual: $actual, expected: $expected);
        }

        return $this;
    }
}
