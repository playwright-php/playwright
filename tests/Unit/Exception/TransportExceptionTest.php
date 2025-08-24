<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Exception\PlaywrightException;
use PlaywrightPHP\Exception\ProcessCrashedException;
use PlaywrightPHP\Exception\ProcessLaunchException;
use PlaywrightPHP\Exception\TransportException;

#[CoversClass(TransportException::class)]
#[CoversClass(ProcessLaunchException::class)]
#[CoversClass(ProcessCrashedException::class)]
class TransportExceptionTest extends TestCase
{
    #[Test]
    public function transportExceptionExtendsPlaywrightException(): void
    {
        $exception = new TransportException('Transport error');

        $this->assertInstanceOf(PlaywrightException::class, $exception);
        $this->assertEquals('Transport error', $exception->getMessage());
    }

    #[Test]
    public function processLaunchExceptionExtendsTransportException(): void
    {
        $exception = new ProcessLaunchException('Launch failed');

        $this->assertInstanceOf(TransportException::class, $exception);
        $this->assertInstanceOf(PlaywrightException::class, $exception);
        $this->assertEquals('Launch failed', $exception->getMessage());
    }

    #[Test]
    public function processLaunchExceptionSupportsContext(): void
    {
        $context = [
            'command' => ['node', 'script.js'],
            'cwd' => '/tmp',
            'stderr' => 'Error: Cannot find module',
        ];

        $exception = new ProcessLaunchException('Failed to start', 0, null, $context);

        $this->assertEquals($context, $exception->getContext());
    }

    #[Test]
    public function processCrashedExceptionExtendsTransportException(): void
    {
        $exception = new ProcessCrashedException('Process crashed', 1, 'stderr output');

        $this->assertInstanceOf(TransportException::class, $exception);
        $this->assertInstanceOf(PlaywrightException::class, $exception);
        $this->assertEquals('Process crashed', $exception->getMessage());
    }

    #[Test]
    public function processCrashedExceptionIncludesExitCodeAndStderr(): void
    {
        $exception = new ProcessCrashedException(
            'Node process died',
            139,
            'Segmentation fault (core dumped)'
        );

        $this->assertEquals(139, $exception->getExitCode());
        $this->assertEquals('Segmentation fault (core dumped)', $exception->getStderrExcerpt());
    }

    #[Test]
    public function processCrashedExceptionAddsDataToContext(): void
    {
        $additionalContext = ['pid' => 1234, 'phase' => 'navigation'];

        $exception = new ProcessCrashedException(
            'Process died during operation',
            1,
            'Error: Out of memory',
            $additionalContext
        );

        $context = $exception->getContext();

        $this->assertEquals(1, $context['exitCode']);
        $this->assertEquals('Error: Out of memory', $context['stderrExcerpt']);
        $this->assertEquals(1234, $context['pid']);
        $this->assertEquals('navigation', $context['phase']);
    }

    #[Test]
    public function processCrashedExceptionSupportsPreviousException(): void
    {
        $previous = new \Exception('Root cause');
        $exception = new ProcessCrashedException(
            'Process crashed',
            1,
            'stderr',
            [],
            $previous
        );

        $this->assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function transportExceptionsCanBeThrown(): void
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Transport failure');

        throw new TransportException('Transport failure');
    }

    #[Test]
    public function processLaunchExceptionCanBeThrown(): void
    {
        $this->expectException(ProcessLaunchException::class);
        $this->expectExceptionMessage('Failed to launch');

        throw new ProcessLaunchException('Failed to launch');
    }

    #[Test]
    public function processCrashedExceptionCanBeThrown(): void
    {
        $this->expectException(ProcessCrashedException::class);
        $this->expectExceptionMessage('Process died');

        throw new ProcessCrashedException('Process died', 1, 'error output');
    }

    #[Test]
    public function exceptionsCanBeCaughtAsPlaywrightException(): void
    {
        $exceptions = [
            new TransportException('transport'),
            new ProcessLaunchException('launch'),
            new ProcessCrashedException('crash', 1, 'stderr'),
        ];

        foreach ($exceptions as $exception) {
            try {
                throw $exception;
            } catch (PlaywrightException $caught) {
                $this->assertSame($exception, $caught);
            }
        }
    }

    #[Test]
    public function exceptionsCanBeCaughtAsTransportException(): void
    {
        $exceptions = [
            new ProcessLaunchException('launch'),
            new ProcessCrashedException('crash', 1, 'stderr'),
        ];

        foreach ($exceptions as $exception) {
            try {
                throw $exception;
            } catch (TransportException $caught) {
                $this->assertSame($exception, $caught);
            }
        }
    }
}
