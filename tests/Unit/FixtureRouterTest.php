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

namespace Playwright\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

#[CoversNothing]
final class FixtureRouterTest extends TestCase
{
    #[DataProvider('pathsOutsideTheFixtureRoot')]
    public function testStaticPathsStayWithinTheFixtureRoot(string $uri): void
    {
        $process = new Process([PHP_BINARY, '-r', '$_SERVER["REQUEST_URI"] = $argv[1]; include $argv[2];', $uri, dirname(__DIR__).'/Fixtures/server.php']);
        $process->mustRun();

        $this->assertStringContainsString('404 Not Found', $process->getOutput());
        $this->assertStringNotContainsString('readfile($file)', $process->getOutput());
    }

    /** @return iterable<string, array{string}> */
    public static function pathsOutsideTheFixtureRoot(): iterable
    {
        yield 'raw parent' => ['/../server.php'];
        yield 'encoded parent' => ['/%2e%2e/server.php'];
        yield 'encoded separator' => ['/%2e%2e%2fserver.php'];
    }

    public function testKnownStaticFilesStillLoad(): void
    {
        $process = new Process([PHP_BINARY, '-r', '$_SERVER["REQUEST_URI"] = "/index.html"; include $argv[1];', dirname(__DIR__).'/Fixtures/server.php']);
        $process->mustRun();

        $this->assertSame(file_get_contents(dirname(__DIR__).'/Fixtures/html/index.html'), $process->getOutput());
    }
}
