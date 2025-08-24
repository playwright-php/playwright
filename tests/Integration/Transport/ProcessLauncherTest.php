<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Exception\ProcessCrashedException;
use PlaywrightPHP\Exception\ProcessLaunchException;
use PlaywrightPHP\Tests\Mocks\TestLogger;
use PlaywrightPHP\Transport\JsonRpc\ProcessLauncher;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

#[CoversClass(ProcessLauncher::class)]
class ProcessLauncherTest extends TestCase
{
    #[Test]
    public function itCanBeInstantiated(): void
    {
        $launcher = new ProcessLauncher();

        $this->assertInstanceOf(ProcessLauncher::class, $launcher);
    }

    #[Test]
    public function itCanBeInstantiatedWithLogger(): void
    {
        $logger = new TestLogger();
        $launcher = new ProcessLauncher($logger);

        $this->assertInstanceOf(ProcessLauncher::class, $launcher);
    }

    #[Test]
    public function itCanLaunchSuccessfulProcess(): void
    {
        $launcher = new ProcessLauncher();

        $process = $launcher->start(['echo', 'hello']);

        $this->assertTrue($process->isRunning() || $process->isSuccessful());

        $process->wait();
        $this->assertTrue($process->isSuccessful());
        $this->assertEquals(0, $process->getExitCode());
    }

    #[Test]
    public function itThrowsOnEmptyCommand(): void
    {
        $launcher = new ProcessLauncher();

        $this->expectException(ProcessLaunchException::class);
        $this->expectExceptionMessage('Command cannot be empty');

        $launcher->start([]);
    }

    #[Test]
    public function itThrowsOnNonExistentCommand(): void
    {
        $launcher = new ProcessLauncher();

        $this->expectException(ProcessLaunchException::class);
        $this->expectExceptionMessage('Failed to launch Node process');

        $launcher->start(['nonexistent_command_that_should_fail']);
    }

    #[Test]
    public function itCapturesStderr(): void
    {
        $launcher = new ProcessLauncher();

        $process = $launcher->start(['php', '-r', 'file_put_contents("php://stderr", "test error");']);
        $process->wait();

        $stderr = $launcher->getStderrOutput();
        $this->assertStringContainsString('test error', $stderr);
    }

    #[Test]
    public function itProvidesStderrBufferInfo(): void
    {
        $launcher = new ProcessLauncher(stderrLinesToKeep: 10);

        $info = $launcher->getStderrBufferInfo();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('lines', $info);
        $this->assertArrayHasKey('maxLines', $info);
        $this->assertArrayHasKey('isEmpty', $info);
        $this->assertEquals(10, $info['maxLines']);
        $this->assertTrue($info['isEmpty']);
    }

    #[Test]
    public function itCanClearStderrBuffer(): void
    {
        $launcher = new ProcessLauncher();

        $process = $launcher->start(['php', '-r', 'file_put_contents("php://stderr", "test");']);
        $process->wait();

        $this->assertNotEmpty($launcher->getStderrOutput());

        $launcher->clearStderrBuffer();

        $this->assertEmpty($launcher->getStderrOutput());
    }

    #[Test]
    public function itCanEnsureProcessIsRunning(): void
    {
        $launcher = new ProcessLauncher();
        $process = $launcher->start(['sleep', '1']);

        $launcher->ensureRunning($process, 'test_phase');

        $this->assertTrue($process->isRunning());

        $process->stop();
    }

    #[Test]
    public function itThrowsWhenProcessNotRunning(): void
    {
        $launcher = new ProcessLauncher();

        $process = $launcher->start(['echo', 'done']);
        $process->wait();

        $this->expectException(ProcessCrashedException::class);
        $this->expectExceptionMessage('Node process not running during test_phase');

        $launcher->ensureRunning($process, 'test_phase');
    }

    #[Test]
    public function itCanTerminateProcess(): void
    {
        $launcher = new ProcessLauncher();
        $process = $launcher->start(['sleep', '10']);

        $this->assertTrue($process->isRunning());

        $launcher->terminate($process, 0.1);

        usleep(200_000);
        $this->assertFalse($process->isRunning());
    }

    #[Test]
    public function itHandlesAlreadyTerminatedProcess(): void
    {
        $launcher = new ProcessLauncher();
        $process = $launcher->start(['echo', 'done']);
        $process->wait();

        $launcher->terminate($process);

        $this->assertFalse($process->isRunning());
    }

    #[Test]
    public function itCanWaitForSuccessfulExit(): void
    {
        $launcher = new ProcessLauncher();
        $process = $launcher->start(['echo', 'success']);

        $exitCode = $launcher->waitForExit($process, 5.0);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function itThrowsOnNonZeroExit(): void
    {
        $launcher = new ProcessLauncher();
        $process = $launcher->start(['php', '-r', 'exit(42);']);

        $this->expectException(ProcessCrashedException::class);
        $this->expectExceptionMessage('Process exited with non-zero code');

        $launcher->waitForExit($process, 5.0);
    }

    #[Test]
    public function itLogsWhenLoggerProvided(): void
    {
        $logger = new TestLogger();
        $launcher = new ProcessLauncher($logger);

        $process = $launcher->start(['echo', 'test']);
        $process->wait();

        $this->assertGreaterThan(0, count($logger->records));

        $messages = array_column($logger->records, 'message');
        $loggedMessages = implode(' ', $messages);
        $this->assertStringContainsString('Node process started successfully', $loggedMessages);
    }

    #[Test]
    public function itIncludesContextInLaunchException(): void
    {
        $launcher = new ProcessLauncher();

        try {
            $launcher->start(['nonexistent_command'], '/tmp', ['TEST_VAR' => 'value']);
        } catch (ProcessLaunchException $e) {
            $context = $e->getContext();

            $this->assertArrayHasKey('command', $context);
            $this->assertArrayHasKey('cwd', $context);
            $this->assertArrayHasKey('env', $context);
            $this->assertEquals(['nonexistent_command'], $context['command']);
            $this->assertEquals('/tmp', $context['cwd']);
            $this->assertEquals(['TEST_VAR'], $context['env']);
        }
    }

    #[Test]
    public function itIncludesContextInCrashException(): void
    {
        $launcher = new ProcessLauncher();

        $process = $launcher->start(['echo', 'done']);
        $process->wait();

        try {
            $launcher->ensureRunning($process, 'navigation');
        } catch (ProcessCrashedException $e) {
            $context = $e->getContext();

            $this->assertArrayHasKey('pid', $context);
            $this->assertArrayHasKey('phase', $context);
            $this->assertArrayHasKey('exitCodeText', $context);
            $this->assertEquals('navigation', $context['phase']);
        }
    }

    #[Test]
    public function itHandlesProcessWithWorkingDirectory(): void
    {
        $launcher = new ProcessLauncher();

        $process = $launcher->start(['pwd'], '/tmp');
        $process->wait();

        $this->assertTrue($process->isSuccessful());
        $this->assertStringContainsString('tmp', $process->getOutput());
    }

    #[Test]
    public function itHandlesProcessWithEnvironment(): void
    {
        $launcher = new ProcessLauncher();

        $process = $launcher->start(
            ['php', '-r', 'echo getenv("TEST_VAR");'],
            null,
            ['TEST_VAR' => 'test_value']
        );
        $process->wait();

        $this->assertTrue($process->isSuccessful());
        $this->assertEquals('test_value', $process->getOutput());
    }

    #[Test]
    public function itHandlesProcessTimeout(): void
    {
        $launcher = new ProcessLauncher();

        $process = $launcher->start(['sleep', '5'], null, [], 0.1);

        try {
            $process->wait();
        } catch (ProcessTimedOutException $e) {
            $this->assertStringContainsString('exceeded the timeout', $e->getMessage());

            return;
        }

        $this->assertFalse($process->isSuccessful());
    }
}
