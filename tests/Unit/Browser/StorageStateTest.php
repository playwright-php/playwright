<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Browser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Browser\StorageState;

#[CoversClass(StorageState::class)]
final class StorageStateTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/playwright-test-'.uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    #[Test]
    public function itCreatesEmptyStorageState(): void
    {
        $storageState = new StorageState();

        $this->assertEmpty($storageState->cookies);
        $this->assertEmpty($storageState->origins);
        $this->assertTrue($storageState->isEmpty());
        $this->assertEquals(0, $storageState->getCookieCount());
        $this->assertEquals(0, $storageState->getOriginCount());
    }

    #[Test]
    public function itCreatesStorageStateWithData(): void
    {
        $cookies = [
            [
                'name' => 'session_id',
                'value' => 'abc123',
                'domain' => 'example.com',
                'path' => '/',
                'expires' => 1234567890,
                'httpOnly' => true,
                'secure' => true,
                'sameSite' => 'Strict',
            ],
        ];

        $origins = [
            [
                'origin' => 'https://example.com',
                'localStorage' => [
                    ['name' => 'user_pref', 'value' => 'dark_mode'],
                    ['name' => 'language', 'value' => 'en'],
                ],
            ],
        ];

        $storageState = new StorageState($cookies, $origins);

        $this->assertEquals($cookies, $storageState->cookies);
        $this->assertEquals($origins, $storageState->origins);
        $this->assertFalse($storageState->isEmpty());
        $this->assertEquals(1, $storageState->getCookieCount());
        $this->assertEquals(1, $storageState->getOriginCount());
    }

    #[Test]
    public function itCreatesFromJson(): void
    {
        $json = '{
            "cookies": [
                {
                    "name": "test_cookie",
                    "value": "test_value",
                    "domain": "test.com",
                    "path": "/",
                    "expires": 1234567890,
                    "httpOnly": false,
                    "secure": false,
                    "sameSite": "Lax"
                }
            ],
            "origins": [
                {
                    "origin": "https://test.com",
                    "localStorage": [
                        {"name": "key1", "value": "value1"}
                    ]
                }
            ]
        }';

        $storageState = StorageState::fromJson($json);

        $this->assertCount(1, $storageState->cookies);
        $this->assertEquals('test_cookie', $storageState->cookies[0]['name']);
        $this->assertEquals('test_value', $storageState->cookies[0]['value']);
        $this->assertCount(1, $storageState->origins);
        $this->assertEquals('https://test.com', $storageState->origins[0]['origin']);
    }

    #[Test]
    public function itThrowsExceptionForInvalidJson(): void
    {
        $this->expectException(\JsonException::class);

        StorageState::fromJson('invalid json');
    }

    #[Test]
    public function itCreatesFromJsonWithMissingProperties(): void
    {
        $json = '{"cookies": []}';

        $storageState = StorageState::fromJson($json);

        $this->assertEmpty($storageState->cookies);
        $this->assertEmpty($storageState->origins);
    }

    #[Test]
    public function itCreatesFromArray(): void
    {
        $data = [
            'cookies' => [
                [
                    'name' => 'array_cookie',
                    'value' => 'array_value',
                    'domain' => 'array.com',
                    'path' => '/',
                    'expires' => 9876543210,
                    'httpOnly' => true,
                    'secure' => false,
                    'sameSite' => 'None',
                ],
            ],
            'origins' => [
                [
                    'origin' => 'https://array.com',
                    'localStorage' => [
                        ['name' => 'array_key', 'value' => 'array_val'],
                    ],
                ],
            ],
        ];

        $storageState = StorageState::fromArray($data);

        $this->assertEquals($data['cookies'], $storageState->cookies);
        $this->assertEquals($data['origins'], $storageState->origins);
    }

    #[Test]
    public function itCreatesFromArrayWithMissingProperties(): void
    {
        $data = ['cookies' => []];

        $storageState = StorageState::fromArray($data);

        $this->assertEmpty($storageState->cookies);
        $this->assertEmpty($storageState->origins);
    }

    #[Test]
    public function itLoadsFromFile(): void
    {
        $filePath = $this->tempDir.'/storage.json';
        $data = [
            'cookies' => [
                [
                    'name' => 'file_cookie',
                    'value' => 'file_value',
                    'domain' => 'file.com',
                    'path' => '/',
                    'expires' => 1111111111,
                    'httpOnly' => false,
                    'secure' => true,
                    'sameSite' => 'Strict',
                ],
            ],
            'origins' => [],
        ];

        file_put_contents($filePath, json_encode($data, JSON_THROW_ON_ERROR));

        $storageState = StorageState::fromFile($filePath);

        $this->assertEquals($data['cookies'], $storageState->cookies);
        $this->assertEmpty($storageState->origins);
    }

    #[Test]
    public function itThrowsExceptionForNonExistentFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Storage state file not found:');

        StorageState::fromFile('/non/existent/file.json');
    }

    #[Test]
    public function itConvertsToJson(): void
    {
        $storageState = new StorageState([
            [
                'name' => 'json_cookie',
                'value' => 'json_value',
                'domain' => 'json.com',
                'path' => '/',
                'expires' => 2222222222,
                'httpOnly' => true,
                'secure' => true,
                'sameSite' => 'Lax',
            ],
        ], [
            [
                'origin' => 'https://json.com',
                'localStorage' => [
                    ['name' => 'json_key', 'value' => 'json_val'],
                ],
            ],
        ]);

        $json = $storageState->toJson();
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('cookies', $decoded);
        $this->assertArrayHasKey('origins', $decoded);
        $this->assertEquals('json_cookie', $decoded['cookies'][0]['name']);
        $this->assertEquals('https://json.com', $decoded['origins'][0]['origin']);
    }

    #[Test]
    public function itConvertsToJsonWithFlags(): void
    {
        $storageState = new StorageState([], []);

        $json = $storageState->toJson(JSON_PRETTY_PRINT);

        $this->assertStringContainsString("\n", $json);
        $this->assertStringContainsString('    ', $json);
    }

    #[Test]
    public function itConvertsToArray(): void
    {
        $cookies = [
            [
                'name' => 'array_test',
                'value' => 'array_test_value',
                'domain' => 'arraytest.com',
                'path' => '/',
                'expires' => 3333333333,
                'httpOnly' => false,
                'secure' => false,
                'sameSite' => 'None',
            ],
        ];

        $origins = [
            [
                'origin' => 'https://arraytest.com',
                'localStorage' => [
                    ['name' => 'test_key', 'value' => 'test_value'],
                ],
            ],
        ];

        $storageState = new StorageState($cookies, $origins);
        $array = $storageState->toArray();

        $this->assertEquals([
            'cookies' => $cookies,
            'origins' => $origins,
        ], $array);
    }

    #[Test]
    public function itSavesToFile(): void
    {
        $filePath = $this->tempDir.'/save_test.json';
        $storageState = new StorageState([
            [
                'name' => 'save_cookie',
                'value' => 'save_value',
                'domain' => 'save.com',
                'path' => '/',
                'expires' => 4444444444,
                'httpOnly' => true,
                'secure' => false,
                'sameSite' => 'Strict',
            ],
        ], []);

        $storageState->saveToFile($filePath);

        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('save_cookie', $decoded['cookies'][0]['name']);
    }

    #[Test]
    public function itCreatesDirectoryWhenSavingToFile(): void
    {
        $nestedPath = $this->tempDir.'/nested/dir/storage.json';
        $storageState = new StorageState();

        $storageState->saveToFile($nestedPath);

        $this->assertFileExists($nestedPath);
        $this->assertDirectoryExists(dirname($nestedPath));
    }

    #[Test]
    public function itDetectsEmptyState(): void
    {
        $emptyState = new StorageState();
        $this->assertTrue($emptyState->isEmpty());

        $withCookies = new StorageState([
            [
                'name' => 'test',
                'value' => 'test',
                'domain' => 'test.com',
                'path' => '/',
                'expires' => 1234567890,
                'httpOnly' => false,
                'secure' => false,
                'sameSite' => 'Lax',
            ],
        ]);
        $this->assertFalse($withCookies->isEmpty());

        $withOrigins = new StorageState([], [
            ['origin' => 'https://test.com', 'localStorage' => []],
        ]);
        $this->assertFalse($withOrigins->isEmpty());
    }

    #[Test]
    public function itCountsCookies(): void
    {
        $storageState = new StorageState([
            ['name' => 'cookie1', 'value' => 'value1', 'domain' => 'test.com', 'path' => '/', 'expires' => 1234567890, 'httpOnly' => false, 'secure' => false, 'sameSite' => 'Lax'],
            ['name' => 'cookie2', 'value' => 'value2', 'domain' => 'test.com', 'path' => '/', 'expires' => 1234567890, 'httpOnly' => false, 'secure' => false, 'sameSite' => 'Lax'],
            ['name' => 'cookie3', 'value' => 'value3', 'domain' => 'other.com', 'path' => '/', 'expires' => 1234567890, 'httpOnly' => false, 'secure' => false, 'sameSite' => 'Lax'],
        ]);

        $this->assertEquals(3, $storageState->getCookieCount());
    }

    #[Test]
    public function itCountsOrigins(): void
    {
        $storageState = new StorageState([], [
            ['origin' => 'https://test.com', 'localStorage' => []],
            ['origin' => 'https://other.com', 'localStorage' => []],
        ]);

        $this->assertEquals(2, $storageState->getOriginCount());
    }

    #[Test]
    public function itFiltersCookiesByDomain(): void
    {
        $storageState = new StorageState([
            ['name' => 'cookie1', 'value' => 'value1', 'domain' => 'test.com', 'path' => '/', 'expires' => 1234567890, 'httpOnly' => false, 'secure' => false, 'sameSite' => 'Lax'],
            ['name' => 'cookie2', 'value' => 'value2', 'domain' => 'test.com', 'path' => '/', 'expires' => 1234567890, 'httpOnly' => false, 'secure' => false, 'sameSite' => 'Lax'],
            ['name' => 'cookie3', 'value' => 'value3', 'domain' => 'other.com', 'path' => '/', 'expires' => 1234567890, 'httpOnly' => false, 'secure' => false, 'sameSite' => 'Lax'],
        ]);

        $testComCookies = $storageState->getCookiesForDomain('test.com');
        $otherComCookies = $storageState->getCookiesForDomain('other.com');
        $nonExistentCookies = $storageState->getCookiesForDomain('nonexistent.com');

        $this->assertCount(2, $testComCookies);
        $this->assertCount(1, $otherComCookies);
        $this->assertCount(0, $nonExistentCookies);

        $this->assertEquals('cookie1', $testComCookies[0]['name']);
        $this->assertEquals('cookie2', $testComCookies[1]['name']);
        $this->assertEquals('cookie3', $otherComCookies[2]['name']);
    }

    #[Test]
    public function itGetsLocalStorageForOrigin(): void
    {
        $storageState = new StorageState([], [
            [
                'origin' => 'https://test.com',
                'localStorage' => [
                    ['name' => 'key1', 'value' => 'value1'],
                    ['name' => 'key2', 'value' => 'value2'],
                ],
            ],
            [
                'origin' => 'https://other.com',
                'localStorage' => [
                    ['name' => 'other_key', 'value' => 'other_value'],
                ],
            ],
        ]);

        $testComStorage = $storageState->getLocalStorageForOrigin('https://test.com');
        $otherComStorage = $storageState->getLocalStorageForOrigin('https://other.com');
        $nonExistentStorage = $storageState->getLocalStorageForOrigin('https://nonexistent.com');

        $this->assertCount(2, $testComStorage);
        $this->assertCount(1, $otherComStorage);
        $this->assertCount(0, $nonExistentStorage);

        $this->assertEquals('key1', $testComStorage[0]['name']);
        $this->assertEquals('value1', $testComStorage[0]['value']);
        $this->assertEquals('other_key', $otherComStorage[0]['name']);
    }

    #[Test]
    public function itGetsEmptyLocalStorageForOriginWithoutLocalStorage(): void
    {
        $storageState = new StorageState([], [
            ['origin' => 'https://test.com'],
        ]);

        $localStorage = $storageState->getLocalStorageForOrigin('https://test.com');

        $this->assertCount(0, $localStorage);
    }

    #[Test]
    public function itIsReadonlyClass(): void
    {
        $storageState = new StorageState([], []);

        $reflection = new \ReflectionClass($storageState);
        $this->assertTrue($reflection->isReadOnly());
    }

    #[Test]
    public function itIsImmutable(): void
    {
        $cookies = [
            ['name' => 'test', 'value' => 'test', 'domain' => 'test.com', 'path' => '/', 'expires' => 1234567890, 'httpOnly' => false, 'secure' => false, 'sameSite' => 'Lax'],
        ];
        $origins = [
            ['origin' => 'https://test.com', 'localStorage' => []],
        ];

        $storageState1 = new StorageState($cookies, $origins);
        $storageState2 = new StorageState($cookies, $origins);

        $this->assertNotSame($storageState1, $storageState2);
        $this->assertEquals($storageState1->cookies, $storageState2->cookies);
        $this->assertEquals($storageState1->origins, $storageState2->origins);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
