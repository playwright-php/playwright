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

namespace Playwright\Tests\Integration\Credentials;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Credentials\Credentials;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;

#[CoversClass(Credentials::class)]
class CredentialsTest extends TestCase
{
    use PlaywrightTestCaseTrait;
    use RouteServerTestTrait;

    public static function setUpBeforeClass(): void
    {
    }

    public static function tearDownAfterClass(): void
    {
    }

    public function setUp(): void
    {
        $this->setUpPlaywright();
        $this->installRouteServer($this->page, [
            '/index.html' => '<!DOCTYPE html><html><body><h1>Passkeys</h1></body></html>',
        ]);
        $this->page->goto($this->routeUrl('/index.html'));
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itSeedsACredentialAndReturnsItsKeys(): void
    {
        $credential = $this->context->credentials()->create('localhost');

        $this->assertSame('localhost', $credential['rpId']);
        $this->assertNotSame('', $credential['id']);
        $this->assertNotSame('', $credential['userHandle']);
        $this->assertNotSame('', $credential['privateKey']);
        $this->assertNotSame('', $credential['publicKey']);
    }

    #[Test]
    public function itReturnsTheSameCredentialsInstanceOnEveryCall(): void
    {
        $this->assertSame($this->context->credentials(), $this->context->credentials());
    }

    #[Test]
    public function itListsSeededCredentials(): void
    {
        $first = $this->context->credentials()->create('localhost');
        $second = $this->context->credentials()->create('example.com');

        $all = $this->context->credentials()->get();

        $this->assertCount(2, $all);
        $this->assertEqualsCanonicalizing(
            [$first['id'], $second['id']],
            array_column($all, 'id')
        );
    }

    #[Test]
    public function itFiltersCredentialsByRelyingParty(): void
    {
        $wanted = $this->context->credentials()->create('localhost');
        $this->context->credentials()->create('example.com');

        $filtered = $this->context->credentials()->get(['rpId' => 'localhost']);

        $this->assertCount(1, $filtered);
        $this->assertSame($wanted['id'], $filtered[0]['id']);
    }

    #[Test]
    public function itFiltersCredentialsById(): void
    {
        $this->context->credentials()->create('localhost');
        $wanted = $this->context->credentials()->create('localhost');

        $filtered = $this->context->credentials()->get(['id' => $wanted['id']]);

        $this->assertCount(1, $filtered);
        $this->assertSame($wanted['id'], $filtered[0]['id']);
    }

    #[Test]
    public function itDeletesACredential(): void
    {
        $kept = $this->context->credentials()->create('localhost');
        $removed = $this->context->credentials()->create('localhost');

        $this->context->credentials()->delete($removed['id']);

        $this->assertSame([$kept['id']], array_column($this->context->credentials()->get(), 'id'));
    }

    #[Test]
    public function itReimportsACredentialFromItsStoredKeys(): void
    {
        $original = $this->context->credentials()->create('localhost');
        $this->context->credentials()->delete($original['id']);

        $reimported = $this->context->credentials()->create('localhost', [
            'id' => $original['id'],
            'userHandle' => $original['userHandle'],
            'privateKey' => $original['privateKey'],
            'publicKey' => $original['publicKey'],
        ]);

        $this->assertSame($original['id'], $reimported['id']);
        $this->assertSame($original['privateKey'], $reimported['privateKey']);
        $this->assertSame($original['publicKey'], $reimported['publicKey']);
    }

    #[Test]
    public function itAnswersThePageWebAuthnCallWithTheSeededCredential(): void
    {
        $this->context->credentials()->install();
        $seeded = $this->context->credentials()->create('localhost');

        $resolved = $this->page->evaluate(<<<'JS'
            async () => {
                const credential = await navigator.credentials.get({
                    publicKey: {
                        challenge: new Uint8Array([1, 2, 3, 4]),
                        rpId: 'localhost',
                        userVerification: 'preferred',
                    },
                });

                return credential ? credential.id : null;
            }
            JS);

        $this->assertSame($seeded['id'], $resolved);
    }

    #[Test]
    public function itReadsBackACredentialThePageRegistered(): void
    {
        $this->context->credentials()->install();

        $registered = $this->page->evaluate(<<<'JS'
            async () => {
                const credential = await navigator.credentials.create({
                    publicKey: {
                        challenge: new Uint8Array([5, 6, 7, 8]),
                        rp: { name: 'Test', id: 'localhost' },
                        user: { id: new Uint8Array([9, 9]), name: 'u@example.com', displayName: 'U' },
                        pubKeyCredParams: [{ type: 'public-key', alg: -7 }],
                    },
                });

                return credential ? credential.id : null;
            }
            JS);

        $this->assertIsString($registered);

        $stored = $this->context->credentials()->get(['id' => $registered]);

        $this->assertCount(1, $stored);
        $this->assertSame('localhost', $stored[0]['rpId']);
        $this->assertNotSame('', $stored[0]['privateKey']);
    }
}
