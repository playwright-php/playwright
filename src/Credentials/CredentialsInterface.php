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

/**
 * Virtual WebAuthn authenticator for a browser context.
 *
 * Lets a test register passkeys and answer the page's
 * `navigator.credentials.create()` and `navigator.credentials.get()` calls
 * without a real authenticator. Every id and key is base64url encoded.
 *
 * @see https://playwright.dev/docs/api/class-credentials
 */
interface CredentialsInterface
{
    /**
     * Seeds a discoverable credential and returns it, private key included, so it can be stored and seeded again later.
     *
     * Any key material left out is generated. To import a known credential, pass
     * `id`, `userHandle`, `privateKey` and `publicKey` together.
     *
     * @param array{id?: string, privateKey?: string, publicKey?: string, userHandle?: string} $options
     *
     * @return array{id: string, rpId: string, userHandle: string, privateKey: string, publicKey: string}
     */
    public function create(string $rpId, array $options = []): array;

    /**
     * Removes the credential with that id, whether it was seeded or registered by the page itself.
     */
    public function delete(string $id): void;

    /**
     * Returns the credentials the authenticator holds, private keys included, narrowed by the given filters.
     *
     * Covers both seeded credentials and the ones the page registered itself.
     *
     * @param array{id?: string, rpId?: string} $options
     *
     * @return list<array{id: string, rpId: string, userHandle: string, privateKey: string, publicKey: string}>
     */
    public function get(array $options = []): array;

    /**
     * Starts intercepting WebAuthn in every page of the context; until this runs the page sees none of the seeded credentials.
     */
    public function install(): void;
}
