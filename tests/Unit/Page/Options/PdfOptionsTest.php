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

namespace Playwright\Tests\Unit\Page\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Exception\RuntimeException;
use Playwright\Page\Options\PdfOptions;

#[CoversClass(PdfOptions::class)]
final class PdfOptionsTest extends TestCase
{
    public function testFromArrayNormalizesValues(): void
    {
        $options = PdfOptions::fromArray([
            'path' => '  /tmp/foo.pdf  ',
            'format' => "\tA4\n",
            'landscape' => 1,
            'scale' => '1.234',
            'printBackground' => 0,
            'width' => ' 100px ',
            'height' => ' 200px ',
            'margin' => [
                'top' => ' 10px ',
                'right' => '5px',
                'bottom' => '',
                'left' => null,
            ],
        ]);

        $this->assertSame([
            'path' => '/tmp/foo.pdf',
            'format' => 'A4',
            'landscape' => true,
            'scale' => 1.23,
            'printBackground' => false,
            'width' => '100px',
            'height' => '200px',
            'margin' => [
                'top' => '10px',
                'right' => '5px',
            ],
        ], $options->toArray());
    }

    public function testFromReturnsSameInstance(): void
    {
        $options = new PdfOptions(path: '/tmp/example.pdf');

        $this->assertSame($options, PdfOptions::from($options));
    }

    public function testFromCreatesFromArray(): void
    {
        $options = PdfOptions::from(['path' => '/tmp/example.pdf']);

        $this->assertSame('/tmp/example.pdf', $options->path());
    }

    public function testScaleMustBeWithinRange(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PDF scale must be between 0.1 and 2.0.');

        new PdfOptions(scale: 3.5);
    }

    public function testScaleMustBeNumeric(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PDF option "scale" must be numeric.');

        PdfOptions::fromArray(['scale' => 'foo']);
    }

    public function testMarginMustBeArray(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PDF option "margin" must be an array of edge => size.');

        PdfOptions::fromArray(['margin' => '10px']);
    }

    public function testWithPathReturnsNewInstance(): void
    {
        $options = new PdfOptions(path: '/tmp/original.pdf');
        $updated = $options->withPath('/tmp/updated.pdf');

        $this->assertNotSame($options, $updated);
        $this->assertSame('/tmp/original.pdf', $options->path());
        $this->assertSame('/tmp/updated.pdf', $updated->path());
    }

    public function testEmptyMarginsAreDropped(): void
    {
        $options = PdfOptions::fromArray([
            'margin' => [
                'top' => '   ',
                'right' => "\n",
            ],
        ]);

        $this->assertArrayNotHasKey('margin', $options->toArray());
    }

    public function testAllNewParametersIncluded(): void
    {
        $options = PdfOptions::fromArray([
            'path' => '/tmp/test.pdf',
            'displayHeaderFooter' => true,
            'footerTemplate' => '  <footer>Page {pageNumber}</footer>  ',
            'headerTemplate' => "\t<header>Title</header>\n",
            'outline' => false,
            'pageRanges' => ' 1-5, 8, 11-13 ',
            'preferCSSPageSize' => true,
            'tagged' => false,
        ]);

        $result = $options->toArray();

        $this->assertSame('/tmp/test.pdf', $result['path']);
        $this->assertTrue($result['displayHeaderFooter']);
        $this->assertSame('<footer>Page {pageNumber}</footer>', $result['footerTemplate']);
        $this->assertSame('<header>Title</header>', $result['headerTemplate']);
        $this->assertFalse($result['outline']);
        $this->assertSame('1-5, 8, 11-13', $result['pageRanges']);
        $this->assertTrue($result['preferCSSPageSize']);
        $this->assertFalse($result['tagged']);
    }

    public function testNewParametersExcludedWhenNull(): void
    {
        $options = PdfOptions::fromArray([
            'path' => '/tmp/test.pdf',
        ]);

        $result = $options->toArray();

        $this->assertArrayHasKey('path', $result);
        $this->assertArrayNotHasKey('displayHeaderFooter', $result);
        $this->assertArrayNotHasKey('footerTemplate', $result);
        $this->assertArrayNotHasKey('headerTemplate', $result);
        $this->assertArrayNotHasKey('outline', $result);
        $this->assertArrayNotHasKey('pageRanges', $result);
        $this->assertArrayNotHasKey('preferCSSPageSize', $result);
        $this->assertArrayNotHasKey('tagged', $result);
    }

    public function testEmptyStringParametersAreNormalized(): void
    {
        $options = PdfOptions::fromArray([
            'footerTemplate' => '   ',
            'headerTemplate' => '',
            'pageRanges' => "\t\n",
        ]);

        $result = $options->toArray();

        $this->assertArrayNotHasKey('footerTemplate', $result);
        $this->assertArrayNotHasKey('headerTemplate', $result);
        $this->assertArrayNotHasKey('pageRanges', $result);
    }

    public function testWithPathPreservesNewParameters(): void
    {
        $options = new PdfOptions(
            path: '/tmp/original.pdf',
            displayHeaderFooter: true,
            outline: true,
            tagged: true
        );

        $updated = $options->withPath('/tmp/updated.pdf');

        $result = $updated->toArray();

        $this->assertSame('/tmp/updated.pdf', $result['path']);
        $this->assertTrue($result['displayHeaderFooter']);
        $this->assertTrue($result['outline']);
        $this->assertTrue($result['tagged']);
    }
}
