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
use Playwright\Locator\LocatorInterface;
use Playwright\Page\PageInterface;

final class Expect
{
    public static function that(mixed $subject): GenericAssertionsInterface
    {
        return new GenericAssertions($subject);
    }

    public static function locator(LocatorInterface $locator): LocatorAssertionsInterface
    {
        return new LocatorAssertions($locator);
    }

    public static function page(PageInterface $page): PageAssertionsInterface
    {
        return new PageAssertions($page);
    }

    public static function response(APIResponseInterface $response): APIResponseAssertionsInterface
    {
        return new APIResponseAssertions($response);
    }
}
