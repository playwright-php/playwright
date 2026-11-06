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

namespace Playwright\Tests\Integration\Popup;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Playwright\Exception\PlaywrightException;
use Playwright\Exception\TimeoutException;
use Playwright\Testing\PlaywrightTestCaseTrait;

#[CoversNothing]
final class PopupFailureTest extends TestCase
{
    use PlaywrightTestCaseTrait;

    protected function setUp(): void
    {
        $this->setUpPlaywright();
    }

    protected function tearDown(): void
    {
        $this->closeSharedPlaywright();
    }

    /** @return iterable<string, array{bool}> */
    public static function popupOwners(): iterable
    {
        yield 'page' => [false];
        yield 'context' => [true];
    }

    #[DataProvider('popupOwners')]
    public function testClosingTheOwnerPreservesTheFailure(bool $useContext): void
    {
        $context = $this->browser->newContext();
        $owner = $useContext ? $context : $context->newPage();

        try {
            $owner->waitForPopup(static function () use ($owner): void {
                $owner->close();
                // The wait rejects before PHP sends the callback continuation.
                usleep(50000);
            }, ['timeout' => 1000]);
            $this->fail('Closing the popup owner must fail the wait.');
        } catch (PlaywrightException $e) {
            $this->assertNotInstanceOf(TimeoutException::class, $e);
            $this->assertStringContainsString('closed', $e->getMessage());
        }

        $this->assertSame('about:blank', $this->page->url());
    }

    #[DataProvider('popupOwners')]
    public function testTimeoutPreservesTheNativeDetails(bool $useContext): void
    {
        $owner = $useContext ? $this->context : $this->page;

        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('Timeout 100ms exceeded');

        $owner->waitForPopup(static function (): void {}, ['timeout' => 100]);
    }

    #[DataProvider('popupOwners')]
    public function testTimeoutBeforeCallbackReturnsDoesNotCrashTheBridge(bool $useContext): void
    {
        $owner = $useContext ? $this->context : $this->page;

        try {
            $owner->waitForPopup(static function (): void {
                usleep(200000);
            }, ['timeout' => 50]);
            $this->fail('The popup wait must time out.');
        } catch (TimeoutException $e) {
            $this->assertStringContainsString('Timeout 50ms exceeded', $e->getMessage());
        }

        $this->assertSame('about:blank', $this->page->url());
    }
}
