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

namespace Playwright\Tests\Unit\Testing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use Playwright\Assertions\Failure\AssertionException;
use Playwright\Assertions\Internal\ScreenshotExpectation;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Browser\BrowserInterface;
use Playwright\Browser\BrowserType;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Locator\Locator;
use Playwright\Locator\LocatorInterface;
use Playwright\Page\Page;
use Playwright\Page\PageInterface;
use Playwright\Testing\Expect;
use Playwright\Testing\ExpectDecorator;
use Playwright\Testing\ExpectInterface;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tracing\TracingInterface;
use Playwright\Transport\TransportInterface;

use function Playwright\Testing\expect;

#[CoversTrait(PlaywrightTestCaseTrait::class)]
#[CoversFunction('Playwright\Testing\expect')]
#[CoversClass(Expect::class)]
#[CoversClass(ExpectDecorator::class)]
final class ExpectFactoryTest extends TestCase
{
    private function createVisibleLocator(): LocatorInterface
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->method('isVisible')->willReturn(true);
        $locator->method('getSelector')->willReturn('#x');

        return $locator;
    }

    public function testTheTraitInjectsTheContextTracing(): void
    {
        $tracing = $this->createMock(TracingInterface::class);
        $tracing->expects($this->once())->method('group');
        $tracing->expects($this->once())->method('groupEnd');

        $context = $this->createMock(BrowserContextInterface::class);
        $context->method('tracing')->willReturn($tracing);

        $harness = new class('harness') extends TestCase {
            use PlaywrightTestCaseTrait;

            public function callExpect(BrowserContextInterface $context, LocatorInterface $subject): ExpectInterface
            {
                $this->context = $context;

                return $this->expect($subject);
            }
        };

        $harness->callExpect($context, $this->createVisibleLocator())
            ->withTimeout(100)
            ->toBeVisible();
    }

    public function testTheExpectFunctionAcceptsATracingHandle(): void
    {
        $tracing = $this->createMock(TracingInterface::class);
        $tracing->expects($this->once())->method('group')->with('expect(#x).toBeVisible');
        $tracing->expects($this->once())->method('groupEnd');

        expect($this->createVisibleLocator(), $tracing)->toBeVisible();
    }

    public function testTheExpectFunctionWorksWithoutTracing(): void
    {
        expect($this->createVisibleLocator())->toBeVisible();

        $this->assertTrue(true);
    }

    public function testTheExpectFunctionExposesLocatorAssertions(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())->method('isAttached')->willReturn(true);
        $locator->method('getSelector')->willReturn('#x');

        expect($locator)->toBeAttached();
    }

    public function testTheExpectFunctionKeepsLegacyToHaveTextContainsSemantics(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())->method('textContent')->willReturn('Welcome Simon');
        $locator->method('getSelector')->willReturn('#x');

        expect($locator)->toHaveText('Welcome');
    }

    public function testLocatorAssertionsRejectAPageSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        expect($this->createMock(PageInterface::class))->toBeVisible();
    }

    public function testPageAssertionsRejectALocatorSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        expect($this->createVisibleLocator())->toHaveTitle('Title');
    }

    public function testNegationDoesNotLeakToTheNextAssertion(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->exactly(2))->method('isVisible')->willReturn(false, true);
        $locator->method('getSelector')->willReturn('#x');
        $expect = expect($locator)->withPollInterval(0);

        $expect->not()->toBeVisible();
        $expect->toBeVisible();
    }

    /** @var list<string> */
    private array $dirs = [];

    public function testRelativeScreenshotNamesGetThePlatformSuffix(): void
    {
        $dir = $this->snapshotDir();

        $this->recordBaseline(new Expect($this->pageReturning('stable'), null, $dir), 'card.png');

        $this->assertSame('stable', file_get_contents($dir.'/card-'.self::platform().'.png'));
    }

    public function testScreenshotNamesWithoutExtensionArePng(): void
    {
        $dir = $this->snapshotDir();

        $this->recordBaseline(new Expect($this->pageReturning('stable'), null, $dir), 'card');

        $this->assertFileExists($dir.'/card-'.self::platform().'.png');
    }

    public function testScreenshotNamesCarryTheBrowserOfThePage(): void
    {
        $dir = $this->snapshotDir();

        $this->recordBaseline(new Expect($this->pageReturning('stable', browser: BrowserType::FIREFOX), null, $dir), 'card.png');

        $this->assertFileExists($dir.'/card-firefox-'.self::platform().'.png');
    }

    public function testLocatorScreenshotNamesCarryTheBrowserOfTheirPage(): void
    {
        $dir = $this->snapshotDir();
        $transport = $this->transportReturning('stable', 'webp', 'locator.expectScreenshot');
        $page = new Page($transport, $this->contextOf(BrowserType::WEBKIT), 'page-id', new PlaywrightConfig());
        $locator = new Locator($transport, 'page-id', '#card', null, null, [], $page);

        $this->recordBaseline(new Expect($locator, null, $dir), 'card.webp');

        $this->assertFileExists($dir.'/card-webkit-'.self::platform().'.webp');
    }

    public function testWebpScreenshotNamesKeepTheirExtension(): void
    {
        $dir = $this->snapshotDir();

        $this->recordBaseline(new Expect($this->pageReturning('stable', type: 'webp'), null, $dir), 'card.webp');

        $this->assertFileExists($dir.'/card-'.self::platform().'.webp');
    }

    public function testAbsoluteScreenshotPathsAreUsedAsIs(): void
    {
        $path = $this->snapshotDir().'/exact.png';
        file_put_contents($path, 'baseline');

        (new Expect($this->pageReturning(null), null, '/elsewhere'))->toHaveScreenshot($path);

        $this->assertSame('baseline', file_get_contents($path));
    }

    public function testWindowsScreenshotPathsAreUsedAsIs(): void
    {
        $_SERVER[ScreenshotExpectation::UPDATE_ENV] = 'none';

        $this->expectException(AssertionException::class);
        $this->expectExceptionMessage('A snapshot doesn\'t exist at C:\\snapshots\\card.png.');

        (new Expect($this->pageReturning('stable'), null, '/elsewhere'))->toHaveScreenshot('C:\\snapshots\\card.png');
    }

    public function testUncScreenshotPathsAreUsedAsIs(): void
    {
        $_SERVER[ScreenshotExpectation::UPDATE_ENV] = 'none';

        $this->expectException(AssertionException::class);
        $this->expectExceptionMessage('A snapshot doesn\'t exist at \\\\server\\share\\card.png.');

        (new Expect($this->pageReturning('stable'), null, '/elsewhere'))->toHaveScreenshot('\\\\server\\share\\card.png');
    }

    public function testScreenshotsWithoutSnapshotDirectoryResolveInTheWorkingDirectory(): void
    {
        $dir = $this->snapshotDir();
        $cwd = (string) getcwd();
        chdir($dir);

        try {
            $this->recordBaseline(new Expect($this->pageReturning('stable')), 'card.png');
        } finally {
            chdir($cwd);
        }

        $this->assertFileExists($dir.'/card-'.self::platform().'.png');
    }

    public function testTheDecoratorCountsTheScreenshotAssertion(): void
    {
        $path = $this->snapshotDir().'/card.png';
        file_put_contents($path, 'baseline');

        $harness = new class('harness') extends TestCase {
        };

        (new ExpectDecorator(new Expect($this->pageReturning(null)), $harness))->toHaveScreenshot($path);

        $this->assertSame(1, $harness->numberOfAssertionsPerformed());
    }

    public function testFailureImagesGoToADirectoryPerBrowser(): void
    {
        $dir = $this->snapshotDir();
        $baseline = $dir.'/card-firefox-'.self::platform().'.png';
        file_put_contents($baseline, 'baseline');
        $_SERVER[ScreenshotExpectation::UPDATE_ENV] = 'none';

        $transport = $this->createStub(TransportInterface::class);
        $transport->method('send')->willReturn(['actual' => base64_encode('actual'), 'diff' => base64_encode('diff'), 'errorMessage' => 'different']);
        $page = new Page($transport, $this->contextOf(BrowserType::FIREFOX), 'page-id', new PlaywrightConfig());

        try {
            (new Expect($page, null, $dir, $dir.'/CardTest-testCard'))->toHaveScreenshot('card.png');
            $this->fail('A mismatch must fail the assertion.');
        } catch (AssertionException $e) {
            $this->assertNotNull($e->actual);
            $out = dirname($e->actual);
        }

        $this->assertStringStartsWith($dir.'/CardTest-testCard-firefox/', $out);
        $this->assertSame(['card-actual.png', 'card-diff.png', 'card-expected.png'], array_map('basename', glob($out.'/*') ?: []));
        foreach (glob($out.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($out);
        rmdir(dirname($out));
    }

    public function testALocatorWithoutPageKeepsThePlatformOnly(): void
    {
        $dir = $this->snapshotDir();
        $transport = $this->transportReturning('stable', 'png', 'locator.expectScreenshot');

        $this->recordBaseline(new Expect(new Locator($transport, 'page-id', '#card'), null, $dir), 'card.png');

        $this->assertFileExists($dir.'/card-'.self::platform().'.png');
    }

    public function testTheTraitStoresSnapshotsNextToTheTestFile(): void
    {
        $harness = new class('harness') extends TestCase {
            use PlaywrightTestCaseTrait;
        };

        $this->assertSame(__FILE__.'-snapshots', (new \ReflectionMethod($harness, 'snapshotDirectory'))->invoke($harness));
    }

    public function testTheTraitGivesEachTestItsOwnOutputDirectory(): void
    {
        $first = new class('testCard') extends TestCase {
            use PlaywrightTestCaseTrait;
        };
        $second = new class('testButton') extends TestCase {
            use PlaywrightTestCaseTrait;
        };

        $method = new \ReflectionMethod($first, 'testOutputDirectory');
        $firstDir = $method->invoke($first);
        $secondDir = (new \ReflectionMethod($second, 'testOutputDirectory'))->invoke($second);

        $this->assertStringStartsWith(getcwd().'/test-failures/', $firstDir);
        $this->assertStringEndsWith('-testCard', $firstDir);
        $this->assertStringEndsWith('-testButton', $secondDir);
        $this->assertDoesNotMatchRegularExpression('~[^A-Za-z0-9._-]~', basename($firstDir));
    }

    public function testTheTraitPassesTheSnapshotDirectoryToExpect(): void
    {
        $dir = $this->snapshotDir();
        $page = $this->pageReturning('stable');

        $context = $this->createStub(BrowserContextInterface::class);
        $context->method('tracing')->willReturn($this->createStub(TracingInterface::class));

        $harness = new class('harness') extends TestCase {
            use PlaywrightTestCaseTrait;

            public string $dir = '';

            public function callExpect(BrowserContextInterface $context, PageInterface $subject): ExpectInterface
            {
                $this->context = $context;

                return $this->expect($subject);
            }

            protected function snapshotDirectory(): string
            {
                return $this->dir;
            }
        };
        $harness->dir = $dir;

        try {
            $harness->callExpect($context, $page)->toHaveScreenshot('card.png');
        } catch (AssertionException) {
        }

        $this->assertFileExists($dir.'/card-'.self::platform().'.png');
    }

    protected function tearDown(): void
    {
        unset($_SERVER[ScreenshotExpectation::UPDATE_ENV]);
        foreach ($this->dirs as $dir) {
            foreach (glob($dir.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($dir);
        }
    }

    private function recordBaseline(Expect $expect, string $name): void
    {
        try {
            $expect->toHaveScreenshot($name);
            $this->fail('Recording a baseline must fail the assertion.');
        } catch (AssertionException $e) {
            $this->assertStringContainsString('writing actual', $e->getMessage());
        }
    }

    /**
     * A page whose bridge returns $actual for every screenshot comparison.
     */
    private function pageReturning(?string $actual, string $type = 'png', ?BrowserType $browser = null): Page
    {
        return new Page($this->transportReturning($actual, $type, 'page.expectScreenshot'), $this->contextOf($browser), 'page-id', new PlaywrightConfig());
    }

    private function transportReturning(?string $actual, string $type, string $action): TransportInterface
    {
        $transport = $this->createStub(TransportInterface::class);
        $transport->method('send')->willReturnCallback(static function (array $payload) use ($actual, $type, $action): array {
            if ($action !== $payload['action'] || $type !== $payload['options']['type']) {
                throw new \UnexpectedValueException('Unexpected command: '.json_encode($payload));
            }

            return ['actual' => null === $actual ? null : base64_encode($actual), 'errorMessage' => null, 'timedOut' => false];
        });

        return $transport;
    }

    /**
     * A context without browser stands for a persistent context.
     */
    private function contextOf(?BrowserType $browser): BrowserContextInterface
    {
        $context = $this->createStub(BrowserContextInterface::class);
        if (null !== $browser) {
            $instance = $this->createStub(BrowserInterface::class);
            $instance->method('browserType')->willReturn($browser);
            $context->method('browser')->willReturn($instance);
        }

        return $context;
    }

    private function snapshotDir(): string
    {
        $dir = sys_get_temp_dir().'/pw-expect-'.bin2hex(random_bytes(6));
        mkdir($dir);
        $this->dirs[] = $dir;

        return $dir;
    }

    private static function platform(): string
    {
        return 'Windows' === \PHP_OS_FAMILY ? 'win32' : strtolower(\PHP_OS_FAMILY);
    }
}
