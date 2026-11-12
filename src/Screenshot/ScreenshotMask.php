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

namespace Playwright\Screenshot;

use Playwright\Locator\Locator;
use Playwright\Locator\LocatorInterface;

/**
 * Validates the mask option of a screenshot and turns its locators into the
 * selectors the bridge resolves back into Playwright locators.
 *
 * @internal
 */
final class ScreenshotMask
{
    /**
     * @param array<mixed> $mask
     *
     * @phpstan-assert array<LocatorInterface> $mask
     *
     * @throws \InvalidArgumentException when an entry is not a locator
     */
    public static function assertLocators(array $mask): void
    {
        foreach ($mask as $index => $locator) {
            if (!$locator instanceof LocatorInterface) {
                throw new \InvalidArgumentException(sprintf('The mask option expects %s instances, %s given at index %s.', LocatorInterface::class, get_debug_type($locator), $index));
            }
        }
    }

    /**
     * @param array<LocatorInterface> $mask
     *
     * @return list<array{selector: string, frameSelector?: string}>
     */
    public static function toArray(array $mask): array
    {
        $targets = [];
        foreach ($mask as $locator) {
            $target = ['selector' => $locator->getSelector()];
            $frameSelector = $locator instanceof Locator ? $locator->getFrameSelector() : null;
            if (null !== $frameSelector) {
                $target['frameSelector'] = $frameSelector;
            }
            $targets[] = $target;
        }

        return $targets;
    }
}
