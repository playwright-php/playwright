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

namespace Playwright\Transport;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

/** @internal */
final class MetadataLogger extends AbstractLogger
{
    private const FIELDS = ['action', 'pageId', 'contextId', 'requestId', 'browser', 'method', 'exceptionType', 'fileCount', 'pid', 'index'];

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public static function wrap(LoggerInterface $logger): LoggerInterface
    {
        if ($logger instanceof self) {
            return $logger;
        }

        return new self($logger);
    }

    /** @param array<string, mixed> $context */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, array_intersect_key($context, array_flip(self::FIELDS)));
    }
}
