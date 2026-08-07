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

namespace Playwright\Tests\Unit\Locator\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Locator\Options\DropPayload;

#[CoversClass(DropPayload::class)]
final class DropPayloadTest extends TestCase
{
    public function testFromWrapsASingleFilePathIntoAList(): void
    {
        $payload = DropPayload::from(['files' => '/tmp/note.txt']);

        $this->assertSame(['/tmp/note.txt'], $payload->files);
        $this->assertSame(['files' => ['/tmp/note.txt']], $payload->toArray());
    }

    public function testFromKeepsAListOfFilePaths(): void
    {
        $payload = DropPayload::from(['files' => ['/tmp/a.txt', '/tmp/b.txt']]);

        $this->assertSame(['files' => ['/tmp/a.txt', '/tmp/b.txt']], $payload->toArray());
    }

    public function testToArrayBase64EncodesInMemoryBuffers(): void
    {
        $payload = new DropPayload(files: [
            ['name' => 'note.txt', 'mimeType' => 'text/plain', 'buffer' => 'hello'],
        ]);

        $this->assertSame([
            'files' => [
                ['name' => 'note.txt', 'mimeType' => 'text/plain', 'buffer' => base64_encode('hello')],
            ],
        ], $payload->toArray());
    }

    public function testToArrayCarriesDataEntriesUnchanged(): void
    {
        $payload = DropPayload::from(['data' => [
            'text/plain' => 'hello world',
            'text/uri-list' => 'https://example.com',
        ]]);

        $this->assertSame(['data' => [
            'text/plain' => 'hello world',
            'text/uri-list' => 'https://example.com',
        ]], $payload->toArray());
    }

    public function testToArrayOmitsEmptySections(): void
    {
        $this->assertSame([], DropPayload::from([])->toArray());
    }

    public function testFromAcceptsSelfInstance(): void
    {
        $original = new DropPayload(data: ['text/plain' => 'x']);

        $this->assertSame($original, DropPayload::from($original));
    }
}
