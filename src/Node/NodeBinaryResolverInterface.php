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

namespace Playwright\Node;

use Playwright\Node\Exception\NodeBinaryNotFoundException;
use Playwright\Node\Exception\NodeVersionTooLowException;

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
