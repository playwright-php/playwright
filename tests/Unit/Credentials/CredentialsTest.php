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

namespace Playwright\Tests\Unit\Credentials;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Credentials\Credentials;
use Playwright\Exception\PlaywrightException;
use Playwright\Exception\ProtocolErrorException;
use Playwright\Transport\MockTransport;

#[CoversClass(Credentials::class)]
final class CredentialsTest extends TestCase
{
    public function testInstallSendsAction(): void
    {
        $transport = $this->transport();
        $transport->queueResponse([]);

        (new Credentials($transport, 'ctx_1'))->install();

        $sent = $transport->getSentMessages();
        $this->assertCount(1, $sent);
        $this->assertSame('credentials.install', $sent[0]['action']);
        $this->assertSame('ctx_1', $sent[0]['contextId']);
    }

    public function testDeleteSendsId(): void
    {
        $transport = $this->transport();
        $transport->queueResponse([]);

        (new Credentials($transport, 'ctx_2'))->delete('cred-id');

        $sent = $transport->getSentMessages();
        $this->assertSame('credentials.delete', $sent[0]['action']);
        $this->assertSame('cred-id', $sent[0]['credentialId']);
        $this->assertArrayNotHasKey('id', $sent[0], 'a top-level id would be taken for the JSON-RPC correlation id');
    }

    public function testCreateSendsRpIdAndReturnsCredential(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['credential' => $this->credential()]);

        $credential = (new Credentials($transport, 'ctx_3'))->create('example.com');

        $sent = $transport->getSentMessages();
        $this->assertSame('credentials.create', $sent[0]['action']);
        $this->assertSame('example.com', $sent[0]['rpId']);
        $this->assertSame([], $sent[0]['options']);
        $this->assertSame($this->credential(), $credential);
    }

    public function testCreateForwardsKeyMaterial(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['credential' => $this->credential()]);

        $options = [
            'id' => 'i',
            'userHandle' => 'u',
            'privateKey' => 'pk',
            'publicKey' => 'pub',
        ];
        (new Credentials($transport, 'ctx_4'))->create('example.com', $options);

        $this->assertSame($options, $transport->getSentMessages()[0]['options']);
    }

    public function testCreateThrowsWhenCredentialIsMissing(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['success' => true]);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid credential response');

        (new Credentials($transport, 'ctx_5'))->create('example.com');
    }

    public function testCreateThrowsWhenAFieldIsMissing(): void
    {
        $credential = $this->credential();
        unset($credential['privateKey']);

        $transport = $this->transport();
        $transport->queueResponse(['credential' => $credential]);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid credential response: missing privateKey');

        (new Credentials($transport, 'ctx_6'))->create('example.com');
    }

    public function testGetReturnsEveryCredential(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['credentials' => [$this->credential(), $this->credential('other')]]);

        $credentials = (new Credentials($transport, 'ctx_7'))->get();

        $this->assertCount(2, $credentials);
        $this->assertSame('cred-id', $credentials[0]['id']);
        $this->assertSame('other', $credentials[1]['id']);
        $this->assertSame('credentials.get', $transport->getSentMessages()[0]['action']);
        $this->assertSame([], $transport->getSentMessages()[0]['options']);
    }

    public function testGetForwardsFilters(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['credentials' => []]);

        $credentials = (new Credentials($transport, 'ctx_8'))->get(['rpId' => 'example.com', 'id' => 'x']);

        $this->assertSame([], $credentials);
        $this->assertSame(['rpId' => 'example.com', 'id' => 'x'], $transport->getSentMessages()[0]['options']);
    }

    public function testGetThrowsWhenPayloadIsMissing(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['success' => true]);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid credentials response');

        (new Credentials($transport, 'ctx_9'))->get();
    }

    public function testGetThrowsWhenAnEntryIsNotAnArray(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['credentials' => ['not-an-array']]);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid credential response');

        (new Credentials($transport, 'ctx_10'))->get();
    }

    public function testItRaisesServerErrors(): void
    {
        $transport = $this->transport();
        $transport->queueResponse(['error' => 'credentials.create: bad rpId']);

        $this->expectException(PlaywrightException::class);
        $this->expectExceptionMessage('credentials.create: bad rpId');

        (new Credentials($transport, 'ctx_err'))->create('example.com');
    }

    /**
     * @return array{id: string, rpId: string, userHandle: string, privateKey: string, publicKey: string}
     */
    private function credential(string $id = 'cred-id'): array
    {
        return [
            'id' => $id,
            'rpId' => 'example.com',
            'userHandle' => 'user-handle',
            'privateKey' => 'private-key',
            'publicKey' => 'public-key',
        ];
    }

    private function transport(): MockTransport
    {
        $transport = new MockTransport();
        $transport->connect();

        return $transport;
    }
}
