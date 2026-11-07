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

namespace Playwright\Tests\Unit\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Exception\ProtocolErrorException;
use Playwright\Page\Options\PageErrorsOptions;
use Playwright\Page\Page;
use Playwright\PageError\PageErrorInterface;
use Playwright\Transport\TransportInterface;

#[CoversClass(Page::class)]
final class PageErrorsTest extends TestCase
{
    public function testItReturnsAnEmptyHistory(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())->method('send')->with([
            'options' => [],
            'action' => 'page.pageErrors',
            'pageId' => 'page-id',
        ])->willReturn(['errors' => []]);

        $page = new Page($transport, $this->createMock(BrowserContextInterface::class), 'page-id', new PlaywrightConfig());

        $this->assertSame([], $page->pageErrors());
    }

    #[DataProvider('provideOptions')]
    public function testItPreservesTheJavaScriptErrors(bool $typedOptions): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())->method('send')->with([
            'options' => ['filter' => 'all'],
            'action' => 'page.pageErrors',
            'pageId' => 'page-id',
        ])->willReturn(['errors' => [
            ['name' => 'TypeError', 'message' => 'Cannot read property', 'stack' => "TypeError: Cannot read property\n    at app.js:12:3"],
            ['name' => 'Error', 'message' => '', 'stack' => null],
            ['name' => '', 'message' => 'Thrown value', 'stack' => ''],
        ]]);

        $page = new Page($transport, $this->createMock(BrowserContextInterface::class), 'page-id', new PlaywrightConfig());
        $options = $typedOptions ? new PageErrorsOptions(filter: 'all') : ['filter' => 'all'];
        $errors = $page->pageErrors($options);

        $this->assertCount(3, $errors);
        $this->assertInstanceOf(PageErrorInterface::class, $errors[0]);
        $this->assertSame('TypeError', $errors[0]->name());
        $this->assertSame('Cannot read property', $errors[0]->message());
        $this->assertSame("TypeError: Cannot read property\n    at app.js:12:3", $errors[0]->stack());
        $this->assertSame('Error', $errors[1]->name());
        $this->assertSame('', $errors[1]->message());
        $this->assertNull($errors[1]->stack());
        $this->assertSame('', $errors[2]->name());
        $this->assertSame('Thrown value', $errors[2]->message());
        $this->assertSame('', $errors[2]->stack());
    }

    public static function provideOptions(): iterable
    {
        yield 'array' => [false];
        yield 'options object' => [true];
    }

    #[DataProvider('provideMalformedResponses')]
    public function testItRejectsMalformedResponses(array $response): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')->willReturn($response);
        $page = new Page($transport, $this->createMock(BrowserContextInterface::class), 'page-id', new PlaywrightConfig());

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid pageErrors');

        $page->pageErrors();
    }

    public static function provideMalformedResponses(): iterable
    {
        yield 'missing history' => [[]];
        yield 'null history' => [['errors' => null]];
        yield 'string history' => [['errors' => 'invalid']];
        yield 'non-list history' => [['errors' => ['first' => ['name' => 'Error', 'message' => 'boom', 'stack' => null]]]];
        yield 'invalid entry' => [['errors' => ['invalid']]];
        yield 'missing name' => [['errors' => [['message' => 'boom', 'stack' => null]]]];
        yield 'invalid name' => [['errors' => [['name' => 123, 'message' => 'boom', 'stack' => null]]]];
        yield 'missing message' => [['errors' => [['name' => 'Error', 'stack' => null]]]];
        yield 'invalid message' => [['errors' => [['name' => 'Error', 'message' => null, 'stack' => null]]]];
        yield 'missing stack' => [['errors' => [['name' => 'Error', 'message' => 'boom']]]];
        yield 'invalid stack' => [['errors' => [['name' => 'Error', 'message' => 'boom', 'stack' => 123]]]];
        yield 'malformed entry after a valid one' => [['errors' => [['name' => 'Error', 'message' => 'boom', 'stack' => null], false]]];
    }
}
