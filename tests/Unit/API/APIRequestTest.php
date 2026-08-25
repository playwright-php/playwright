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

namespace Playwright\Tests\Unit\API;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\API\APIRequest;
use Playwright\API\APIRequestContext;
use Playwright\Transport\TransportInterface;

#[CoversClass(APIRequest::class)]
final class APIRequestTest extends TestCase
{
    public function testNewContextForwardsOptionsAndCreatesAnIsolatedContext(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $options = [
            'baseURL' => 'https://example.test/api',
            'extraHTTPHeaders' => ['Accept' => 'application/json'],
            'storageState' => ['cookies' => [], 'origins' => []],
        ];

        $transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'api.newContext',
                'options' => $options,
            ])
            ->willReturn(['contextId' => 'api_1']);

        $context = (new APIRequest($transport))->newContext($options);

        $this->assertInstanceOf(APIRequestContext::class, $context);
        $this->assertFalse($context->getShareCookies());
    }

    public function testNewContextRejectsAnInvalidResponse(): void
    {
        $transport = $this->createStub(TransportInterface::class);
        $transport->method('send')->willReturn(['success' => true]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to create API request context');

        (new APIRequest($transport))->newContext();
    }
}
