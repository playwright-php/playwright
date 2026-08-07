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

namespace Playwright\Tests\Unit\WebStorage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Exception\PlaywrightException;
use Playwright\Exception\ProtocolErrorException;
use Playwright\Transport\MockTransport;
use Playwright\WebStorage\WebStorage;

#[CoversClass(WebStorage::class)]
final class WebStorageTest extends TestCase
{
    public function testClearSendsAction(): void
    {
        $transport = $this->transport();
        $transport->queueResponse([]);

        (new WebStorage($transport, 'page_1', 'localStorage'))->clear();

        $sent = $transport->getSentMessages();
        $this->assertCount(1, $sent);
        $this->assertSame('webStorage.clear', $sent[0]['action']);
        $this->assertSame('page_1', $sent[0]['pageId']);
        $this->assertSame('localStorage', $sent[0]['storage']);
    }

    public function testSetItemSendsNameAndValue(): void
    {
        $transport = $this->transport();
        $transport->queueResponse([]);

        (new WebStorage($transport, 'page_2', 'sessionStorage'))->setItem('token', 'abc');

        $sent = $transport->getSentMessages();
        $this->assertSame('webStorage.setItem', $sent[0]['action']);
        $this->assertSame('sessionStorage', $sent[0]['storage']);
        $this->assertSame('token', $sent[0]['name']);
        $this->assertSame('abc', $sent[0]['value']);
    }

    public function testRemoveItemSendsName(): void
    {
        $transport = $this->transport();
        $transport->queueResponse([]);

        (new WebStorage($transport, 'page_3', 'localStorage'))->removeItem('token');

        $sent = $transport->getSentMessages();
        $this->assertSame('webStorage.removeItem', $sent[0]['action']);
        $this->assertSame('token', $sent[0]['name']);
    }

    public function testGetItemReturnsStringValue(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['value' => 'abc']);

        $value = (new WebStorage($transport, 'page_4', 'localStorage'))->getItem('token');

        $this->assertSame('abc', $value);
        $sent = $transport->getSentMessages();
        $this->assertSame('webStorage.getItem', $sent[0]['action']);
        $this->assertSame('token', $sent[0]['name']);
    }

    public function testGetItemReturnsNullForMissingItem(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['value' => null]);

        $this->assertNull((new WebStorage($transport, 'page_5', 'localStorage'))->getItem('nope'));
    }

    public function testGetItemReturnsNullWhenValueIsNotAString(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['success' => true]);

        $this->assertNull((new WebStorage($transport, 'page_6', 'localStorage'))->getItem('nope'));
    }

    public function testItemsReturnsNameValuePairs(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['items' => [
            ['name' => 'a', 'value' => '1'],
            ['name' => 'b', 'value' => '2'],
        ]]);

        $items = (new WebStorage($transport, 'page_7', 'localStorage'))->items();

        $this->assertSame([
            ['name' => 'a', 'value' => '1'],
            ['name' => 'b', 'value' => '2'],
        ], $items);
        $this->assertSame('webStorage.items', $transport->getSentMessages()[0]['action']);
    }

    public function testItemsReturnsEmptyArrayForEmptyStorage(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['items' => []]);

        $this->assertSame([], (new WebStorage($transport, 'page_8', 'localStorage'))->items());
    }

    public function testItemsThrowsWhenPayloadIsMissing(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['success' => true]);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid web storage items response');

        (new WebStorage($transport, 'page_9', 'localStorage'))->items();
    }

    public function testItemsThrowsWhenAnEntryIsNotAnArray(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['items' => ['not-an-array']]);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid web storage item response');

        (new WebStorage($transport, 'page_10', 'localStorage'))->items();
    }

    public function testItemsThrowsWhenAnEntryHasNoStringValue(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['items' => [['name' => 'a', 'value' => 1]]]);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid web storage item response');

        (new WebStorage($transport, 'page_11', 'localStorage'))->items();
    }

    public function testItRaisesServerErrors(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['error' => 'SecurityError: Storage is disabled']);

        $this->expectException(PlaywrightException::class);
        $this->expectExceptionMessage('SecurityError: Storage is disabled');

        (new WebStorage($transport, 'page_err', 'localStorage'))->setItem('a', '1');
    }

    public function testItRaisesAGenericMessageForANonStringError(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['error' => ['message' => 'nope']]);

        $this->expectException(PlaywrightException::class);
        $this->expectExceptionMessage('Unknown Playwright server error');

        (new WebStorage($transport, 'page_err_2', 'localStorage'))->clear();
    }

    private function transport(): MockTransport
    {
        $transport = new MockTransport();
        $transport->connect();

        return $transport;
    }
}
