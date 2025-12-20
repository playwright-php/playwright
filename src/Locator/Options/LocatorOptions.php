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

namespace Playwright\Locator\Options;

use Playwright\Exception\RuntimeException;
use Playwright\Locator\LocatorInterface;

/**
 * @phpstan-type LocatorOptionsArray array{
 *     has?: LocatorInterface,
 *     hasNot?: LocatorInterface,
 *     hasText?: string,
 *     hasNotText?: string,
 *     strict?: bool
 * }
 */
final readonly class LocatorOptions
{
    private const KNOWN_KEYS = ['has', 'hasNot', 'hasText', 'hasNotText', 'strict'];

    /**
     * @param array<string, mixed> $extras
     */
    public function __construct(
        public ?LocatorInterface $has = null,
        public ?LocatorInterface $hasNot = null,
        public ?string $hasText = null,
        public ?string $hasNotText = null,
        public ?bool $strict = null,
        private array $extras = [],
    ) {
    }

    /**
     * @param array<string, mixed>|self $options
     */
    public static function from(array|self $options): self
    {
        if ($options instanceof self) {
            return $options;
        }

        return self::fromArray($options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function fromArray(array $options): self
    {
        $has = self::extractLocator($options, 'has');
        $hasNot = self::extractLocator($options, 'hasNot');
        $hasText = self::extractString($options, 'hasText');
        $hasNotText = self::extractString($options, 'hasNotText');
        $strict = array_key_exists('strict', $options) ? self::extractBool($options['strict'], 'strict') : null;

        $extras = self::collectExtras($options);

        return new self($has, $hasNot, $hasText, $hasNotText, $strict, $extras);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = $this->extras;

        if (null !== $this->has) {
            $options['has'] = $this->has;
        }
        if (null !== $this->hasNot) {
            $options['hasNot'] = $this->hasNot;
        }
        if (null !== $this->hasText) {
            $options['hasText'] = $this->hasText;
        }
        if (null !== $this->hasNotText) {
            $options['hasNotText'] = $this->hasNotText;
        }
        if (null !== $this->strict) {
            $options['strict'] = $this->strict;
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function extractLocator(array $options, string $key): ?LocatorInterface
    {
        if (!array_key_exists($key, $options)) {
            return null;
        }

        $value = $options[$key];
        if (null === $value) {
            return null;
        }

        if (!$value instanceof LocatorInterface) {
            throw new RuntimeException(sprintf('Locator option "%s" must be a Locator instance or null.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function extractString(array $options, string $key): ?string
    {
        if (!array_key_exists($key, $options)) {
            return null;
        }

        $value = $options[$key];
        if (null === $value) {
            return null;
        }

        if (!is_scalar($value) && !$value instanceof \Stringable) {
            throw new RuntimeException(sprintf('Locator option "%s" must be stringable.', $key));
        }

        return (string) $value;
    }

    private static function extractBool(mixed $value, string $key): bool
    {
        if (!is_bool($value)) {
            throw new RuntimeException(sprintf('Locator option "%s" must be boolean.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private static function collectExtras(array $options): array
    {
        $extras = [];
        foreach ($options as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (in_array($key, self::KNOWN_KEYS, true)) {
                continue;
            }

            $extras[$key] = $value;
        }

        return $extras;
    }
}
