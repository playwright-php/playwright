<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Testing;

use PlaywrightPHP\Locator\LocatorInterface;
use PlaywrightPHP\Page\PageInterface;

function expect(LocatorInterface|PageInterface $subject): ExpectInterface
{
    return new Expect($subject);
}
