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

namespace Playwright\Tests\Integration\Testing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Testing\PlaywrightTestCase;
use Playwright\Testing\PlaywrightTestCaseTrait;

#[CoversClass(PlaywrightTestCase::class)]
class FailureTest extends TestCase
{
    use PlaywrightTestCaseTrait;

    public function setUp(): void
    {
        $this->setUpPlaywright();
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itFailsAndCreatesArtifacts(): void
    {
        try {
            $this->page->setContent('<h1>Failure Test</h1>');
            $this->assertStringContainsString('Failure Test', $this->page->locator('h1')->textContent());
        } finally {
            $failuresDir = getcwd().'/test-failures';
            if (!is_dir($failuresDir)) {
                mkdir($failuresDir, 0777, true);
            }
            $this->page->screenshot($failuresDir.'/'.__METHOD__.'.png');
            $this->context->stopTracing($this->page, $failuresDir.'/'.__METHOD__.'.zip');
        }
    }
}
