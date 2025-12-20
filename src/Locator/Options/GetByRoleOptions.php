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

/**
 * @phpstan-type GetByRoleOptionsArray array{
 *     checked?: bool,
 *     disabled?: bool,
 *     expanded?: bool,
 *     includeHidden?: bool,
 *     level?: int,
 *     name?: string,
 *     pressed?: bool,
 *     selected?: bool
 * }
 */
final readonly class GetByRoleOptions
{
    private const ROLE_KEYS = ['checked', 'disabled', 'expanded', 'includeHidden', 'level', 'name', 'pressed', 'selected', 'exact'];

    public function __construct(
        public ?bool $checked = null,
        public ?bool $disabled = null,
        public ?bool $exact = null,
        public ?bool $expanded = null,
        public ?bool $includeHidden = null,
        public ?int $level = null,
        public ?string $name = null,
        public bool|string|null $pressed = null,
        public ?bool $selected = null,
        public LocatorOptions $locatorOptions = new LocatorOptions(),
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

        $locatorOptions = LocatorOptions::fromArray($options);

        return new self(
            checked: self::extractBool($options, 'checked'),
            disabled: self::extractBool($options, 'disabled'),
            exact: self::extractBool($options, 'exact'),
            expanded: self::extractBool($options, 'expanded'),
            includeHidden: self::extractBool($options, 'includeHidden'),
            level: self::extractLevel($options),
            name: self::extractName($options),
            pressed: self::extractPressed($options),
            selected: self::extractBool($options, 'selected'),
            locatorOptions: $locatorOptions,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = $this->filteredLocatorOptions();

        $this->appendIfNotNull($options, 'checked', $this->checked);
        $this->appendIfNotNull($options, 'disabled', $this->disabled);
        $this->appendIfNotNull($options, 'exact', $this->exact);
        $this->appendIfNotNull($options, 'expanded', $this->expanded);
        $this->appendIfNotNull($options, 'includeHidden', $this->includeHidden);
        $this->appendIfNotNull($options, 'level', $this->level);
        $this->appendIfNotNull($options, 'name', $this->name);
        $this->appendIfNotNull($options, 'pressed', $this->pressed);
        $this->appendIfNotNull($options, 'selected', $this->selected);

        return $options;
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function extractBool(array $options, string $key): ?bool
    {
        if (!array_key_exists($key, $options)) {
            return null;
        }

        $value = $options[$key];
        if (null === $value) {
            return null;
        }

        if (!is_bool($value)) {
            throw new RuntimeException(sprintf('getByRole option "%s" must be boolean.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function extractLevel(array $options): ?int
    {
        if (!array_key_exists('level', $options)) {
            return null;
        }

        $value = $options['level'];
        if (null === $value) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        throw new RuntimeException('getByRole option "level" must be an integer.');
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function extractPressed(array $options): bool|string|null
    {
        if (!array_key_exists('pressed', $options)) {
            return null;
        }

        $value = $options['pressed'];
        if (null === $value) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value) && 'mixed' === $value) {
            return 'mixed';
        }

        throw new RuntimeException('getByRole option "pressed" must be boolean or "mixed".');
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function extractName(array $options): ?string
    {
        if (!array_key_exists('name', $options)) {
            return null;
        }

        $value = $options['name'];
        if (null === $value) {
            return null;
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        throw new RuntimeException('getByRole option "name" must be stringable.');
    }

    /**
     * @return array<string, mixed>
     */
    private function filteredLocatorOptions(): array
    {
        $options = $this->locatorOptions->toArray();
        foreach (self::ROLE_KEYS as $key) {
            if (array_key_exists($key, $options)) {
                unset($options[$key]);
            }
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function appendIfNotNull(array &$options, string $key, mixed $value): void
    {
        if (null === $value) {
            return;
        }

        $options[$key] = $value;
    }
}
