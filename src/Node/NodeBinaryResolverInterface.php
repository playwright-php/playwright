<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Node;

use PlaywrightPHP\Node\Exception\NodeBinaryNotFoundException;
use PlaywrightPHP\Node\Exception\NodeVersionTooLowException;

/**
 * Interface for Node.js binary resolution.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface NodeBinaryResolverInterface
{
    /**
     * Resolve Node.js binary path with version validation.
     *
     * @throws NodeBinaryNotFoundException
     * @throws NodeVersionTooLowException
     */
    public function resolve(): string;
}
