<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Mocks;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class TestLogger implements LoggerInterface
{
    use LoggerTrait;

    public array $records = [];

    public function log($level, $message, array $context = []): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function hasInfoThatContains(string $string): bool
    {
        foreach ($this->records as $record) {
            if ('info' === $record['level'] && str_contains($record['message'], $string)) {
                return true;
            }
        }

        return false;
    }

    public function hasDebugRecords(): bool
    {
        foreach ($this->records as $record) {
            if ('debug' === $record['level']) {
                return true;
            }
        }

        return false;
    }

    public function hasInfoRecords(): bool
    {
        foreach ($this->records as $record) {
            if ('info' === $record['level']) {
                return true;
            }
        }

        return false;
    }

    public function hasWarningRecords(): bool
    {
        foreach ($this->records as $record) {
            if ('warning' === $record['level']) {
                return true;
            }
        }

        return false;
    }
}
