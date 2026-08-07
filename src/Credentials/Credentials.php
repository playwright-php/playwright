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

namespace Playwright\Credentials;

use Playwright\Exception\PlaywrightException;
use Playwright\Exception\ProtocolErrorException;
use Playwright\Transport\TransportInterface;

final class Credentials implements CredentialsInterface
{
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $contextId,
    ) {
    }

    public function create(string $rpId, array $options = []): array
    {
        $response = $this->send([
            'action' => 'credentials.create',
            'contextId' => $this->contextId,
            'rpId' => $rpId,
            'options' => $options,
        ]);

        $credential = $response['credential'] ?? null;
        if (!is_array($credential)) {
            throw new ProtocolErrorException('Invalid credential response', 0);
        }

        return $this->toCredential($credential);
    }

    public function delete(string $id): void
    {
        // Not 'id': a top-level id in the payload is taken for the JSON-RPC
        // correlation id and the reply would never be matched.
        $this->send([
            'action' => 'credentials.delete',
            'contextId' => $this->contextId,
            'credentialId' => $id,
        ]);
    }

    public function get(array $options = []): array
    {
        $response = $this->send([
            'action' => 'credentials.get',
            'contextId' => $this->contextId,
            'options' => $options,
        ]);

        $credentials = $response['credentials'] ?? null;
        if (!is_array($credentials)) {
            throw new ProtocolErrorException('Invalid credentials response', 0);
        }

        $result = [];
        foreach ($credentials as $credential) {
            if (!is_array($credential)) {
                throw new ProtocolErrorException('Invalid credential response', 0);
            }

            $result[] = $this->toCredential($credential);
        }

        return $result;
    }

    public function install(): void
    {
        $this->send([
            'action' => 'credentials.install',
            'contextId' => $this->contextId,
        ]);
    }

    /**
     * @param array<mixed> $credential
     *
     * @return array{id: string, rpId: string, userHandle: string, privateKey: string, publicKey: string}
     */
    private function toCredential(array $credential): array
    {
        $fields = [];
        foreach (['id', 'rpId', 'userHandle', 'privateKey', 'publicKey'] as $field) {
            $value = $credential[$field] ?? null;
            if (!is_string($value)) {
                throw new ProtocolErrorException(sprintf('Invalid credential response: missing %s', $field), 0);
            }

            $fields[$field] = $value;
        }

        return [
            'id' => $fields['id'],
            'rpId' => $fields['rpId'],
            'userHandle' => $fields['userHandle'],
            'privateKey' => $fields['privateKey'],
            'publicKey' => $fields['publicKey'],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function send(array $payload): array
    {
        $response = $this->transport->send($payload);

        if (isset($response['error'])) {
            $error = $response['error'];

            throw new PlaywrightException(is_string($error) ? $error : 'Unknown Playwright server error');
        }

        return $response;
    }
}
