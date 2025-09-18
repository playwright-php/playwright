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

namespace Playwright\Node\Exception;

use Playwright\Exception\PlaywrightException;

/**
 * Thrown when Node.js binary cannot be found on the system.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class NodeBinaryNotFoundException extends PlaywrightException
{
}
