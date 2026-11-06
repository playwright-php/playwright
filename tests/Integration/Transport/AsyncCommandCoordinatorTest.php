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

namespace Playwright\Tests\Integration\Transport;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

#[CoversNothing]
final class AsyncCommandCoordinatorTest extends TestCase
{
    public function testCoordinatorExpiration(): void
    {
        $node = (new ExecutableFinder())->find('node');
        if (null === $node) {
            $this->markTestSkipped('Node.js executable not found.');
        }

        $process = new Process([$node, '--test', __DIR__.'/../../Fixtures/coordinator-expiration.cjs']);
        $process->setTimeout(10);
        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getOutput().$process->getErrorOutput());
    }
}
