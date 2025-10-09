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

namespace Playwright\Testing;

use PHPUnit\Framework\TestCase;

abstract class PlaywrightTestCase extends TestCase
{
    use PlaywrightTestCaseTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPlaywright();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->tearDownPlaywright();
        parent::tearDown();
    }
}
