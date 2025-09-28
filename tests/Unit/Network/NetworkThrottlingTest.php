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

namespace Playwright\Tests\Unit\Network;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Network\NetworkThrottling;

#[CoversClass(NetworkThrottling::class)]
final class NetworkThrottlingTest extends TestCase
{
    #[Test]
    public function itCreatesNetworkThrottlingWithConstructor(): void
    {
        $throttling = new NetworkThrottling(1024, 512, 100);

        $this->assertEquals(1024, $throttling->downloadThroughput);
        $this->assertEquals(512, $throttling->uploadThroughput);
        $this->assertEquals(100, $throttling->latency);
    }

    #[Test]
    public function itCreatesNoneThrottling(): void
    {
        $throttling = NetworkThrottling::none();

        $this->assertEquals(0, $throttling->downloadThroughput);
        $this->assertEquals(0, $throttling->uploadThroughput);
        $this->assertEquals(0, $throttling->latency);
        $this->assertTrue($throttling->isDisabled());
    }

    #[Test]
    public function itCreatesSlow3GThrottling(): void
    {
        $throttling = NetworkThrottling::slow3G();

        $this->assertEquals(50 * 1024, $throttling->downloadThroughput);
        $this->assertEquals(50 * 1024, $throttling->uploadThroughput);
        $this->assertEquals(2000, $throttling->latency);
        $this->assertFalse($throttling->isDisabled());
    }

    #[Test]
    public function itCreatesFast3GThrottling(): void
    {
        $throttling = NetworkThrottling::fast3G();

        $this->assertEquals(150 * 1024, $throttling->downloadThroughput);
        $this->assertEquals(75 * 1024, $throttling->uploadThroughput);
        $this->assertEquals(562, $throttling->latency);
        $this->assertFalse($throttling->isDisabled());
    }

    #[Test]
    public function itCreatesFast4GThrottling(): void
    {
        $throttling = NetworkThrottling::fast4G();

        $this->assertEquals((int) (1.6 * 1024 * 1024), $throttling->downloadThroughput);
        $this->assertEquals(750 * 1024, $throttling->uploadThroughput);
        $this->assertEquals(150, $throttling->latency);
        $this->assertFalse($throttling->isDisabled());
    }

    #[Test]
    public function itCreatesDslThrottling(): void
    {
        $throttling = NetworkThrottling::dsl();

        $this->assertEquals(2 * 1024 * 1024, $throttling->downloadThroughput);
        $this->assertEquals(1 * 1024 * 1024, $throttling->uploadThroughput);
        $this->assertEquals(5, $throttling->latency);
        $this->assertFalse($throttling->isDisabled());
    }

    #[Test]
    public function itCreatesWifiThrottling(): void
    {
        $throttling = NetworkThrottling::wifi();

        $this->assertEquals(30 * 1024 * 1024, $throttling->downloadThroughput);
        $this->assertEquals(15 * 1024 * 1024, $throttling->uploadThroughput);
        $this->assertEquals(2, $throttling->latency);
        $this->assertFalse($throttling->isDisabled());
    }

    #[Test]
    public function itCreatesCustomThrottling(): void
    {
        $throttling = NetworkThrottling::custom(1000, 500, 250);

        $this->assertEquals(1000, $throttling->downloadThroughput);
        $this->assertEquals(500, $throttling->uploadThroughput);
        $this->assertEquals(250, $throttling->latency);
        $this->assertFalse($throttling->isDisabled());
    }

    #[Test]
    public function itConvertsToArray(): void
    {
        $throttling = new NetworkThrottling(1024, 512, 100);

        $array = $throttling->toArray();

        $this->assertEquals([
            'downloadThroughput' => 1024,
            'uploadThroughput' => 512,
            'latency' => 100,
        ], $array);
    }

    #[Test]
    #[DataProvider('isDisabledProvider')]
    public function itChecksIfThrottlingIsDisabled(int $download, int $upload, int $latency, bool $expected): void
    {
        $throttling = new NetworkThrottling($download, $upload, $latency);

        $this->assertEquals($expected, $throttling->isDisabled());
    }

    public static function isDisabledProvider(): array
    {
        return [
            'all zero' => [0, 0, 0, true],
            'download non-zero' => [100, 0, 0, false],
            'upload non-zero' => [0, 100, 0, false],
            'latency non-zero' => [0, 0, 100, false],
            'all non-zero' => [100, 200, 50, false],
        ];
    }

    #[Test]
    public function itGetsDescriptionForDisabledThrottling(): void
    {
        $throttling = NetworkThrottling::none();

        $description = $throttling->getDescription();

        $this->assertEquals('No throttling', $description);
    }

    #[Test]
    #[DataProvider('descriptionProvider')]
    public function itGetsDescriptionWithFormattedValues(int $download, int $upload, int $latency, string $expected): void
    {
        $throttling = new NetworkThrottling($download, $upload, $latency);

        $description = $throttling->getDescription();

        $this->assertEquals($expected, $description);
    }

    public static function descriptionProvider(): array
    {
        return [
            'bytes per second' => [
                100,
                50,
                25,
                'Download: 100 B/s, Upload: 50 B/s, Latency: 25ms',
            ],
            'kilobytes per second' => [
                1024,
                2048,
                100,
                'Download: 1 KB/s, Upload: 2 KB/s, Latency: 100ms',
            ],
            'megabytes per second' => [
                2 * 1024 * 1024,
                1024 * 1024,
                150,
                'Download: 2.0 MB/s, Upload: 1.0 MB/s, Latency: 150ms',
            ],
            'fractional megabytes' => [
                (int) (1.5 * 1024 * 1024),
                (int) (0.75 * 1024 * 1024),
                75,
                'Download: 1.5 MB/s, Upload: 0.8 MB/s, Latency: 75ms',
            ],
            'mixed units' => [
                512 * 1024,
                1024 * 1024,
                200,
                'Download: 512 KB/s, Upload: 1.0 MB/s, Latency: 200ms',
            ],
        ];
    }

    #[Test]
    public function itFormatsPresetDescriptionsCorrectly(): void
    {
        $descriptions = [
            NetworkThrottling::none()->getDescription() => 'No throttling',
            NetworkThrottling::slow3G()->getDescription() => 'Download: 50 KB/s, Upload: 50 KB/s, Latency: 2000ms',
            NetworkThrottling::fast3G()->getDescription() => 'Download: 150 KB/s, Upload: 75 KB/s, Latency: 562ms',
            NetworkThrottling::fast4G()->getDescription() => 'Download: 1.6 MB/s, Upload: 750 KB/s, Latency: 150ms',
            NetworkThrottling::dsl()->getDescription() => 'Download: 2.0 MB/s, Upload: 1.0 MB/s, Latency: 5ms',
            NetworkThrottling::wifi()->getDescription() => 'Download: 30.0 MB/s, Upload: 15.0 MB/s, Latency: 2ms',
        ];

        foreach ($descriptions as $actual => $expected) {
            $this->assertEquals($expected, $actual);
        }
    }

    #[Test]
    public function itIsReadonlyClass(): void
    {
        $throttling = new NetworkThrottling(1024, 512, 100);

        $this->assertEquals(1024, $throttling->downloadThroughput);
        $this->assertEquals(512, $throttling->uploadThroughput);
        $this->assertEquals(100, $throttling->latency);

        $reflection = new \ReflectionClass($throttling);
        $this->assertTrue($reflection->isReadOnly());
    }

    #[Test]
    public function itIsImmutable(): void
    {
        $throttling1 = NetworkThrottling::slow3G();
        $throttling2 = NetworkThrottling::slow3G();

        $this->assertNotSame($throttling1, $throttling2);
        $this->assertEquals($throttling1->downloadThroughput, $throttling2->downloadThroughput);
        $this->assertEquals($throttling1->uploadThroughput, $throttling2->uploadThroughput);
        $this->assertEquals($throttling1->latency, $throttling2->latency);
    }

    #[Test]
    public function itHandlesZeroValuesInCustomThrottling(): void
    {
        $throttling = NetworkThrottling::custom(0, 0, 0);

        $this->assertTrue($throttling->isDisabled());
        $this->assertEquals('No throttling', $throttling->getDescription());
        $this->assertEquals([
            'downloadThroughput' => 0,
            'uploadThroughput' => 0,
            'latency' => 0,
        ], $throttling->toArray());
    }

    #[Test]
    public function itHandlesLargeThroughputValues(): void
    {
        $largeDownload = 100 * 1024 * 1024;
        $largeUpload = 50 * 1024 * 1024;
        $throttling = NetworkThrottling::custom($largeDownload, $largeUpload, 1);

        $this->assertEquals($largeDownload, $throttling->downloadThroughput);
        $this->assertEquals($largeUpload, $throttling->uploadThroughput);
        $this->assertEquals('Download: 100.0 MB/s, Upload: 50.0 MB/s, Latency: 1ms', $throttling->getDescription());
    }
}
