<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Page\Page;
use PlaywrightPHP\Testing\PlaywrightTestCaseTrait;
use Symfony\Component\Process\Process;

#[CoversClass(Page::class)]
class PageTest extends TestCase
{
    use PlaywrightTestCaseTrait;

    private static ?Process $server = null;
    private static string $docroot;
    private static int $port;

    public static function setUpBeforeClass(): void
    {
        self::$docroot = sys_get_temp_dir().'/playwright-php-tests-'.uniqid('', true);
        mkdir(self::$docroot);

        file_put_contents(self::$docroot.'/index.html', '<h1>Hello World</h1><a href="/page2.html">link</a>');
        file_put_contents(self::$docroot.'/page2.html', '<h2>Page 2</h2>');
        file_put_contents(self::$docroot.'/script.js', 'window.myVar = 123;');
        file_put_contents(self::$docroot.'/style.css', 'h1 { color: red; }');

        self::$port = self::findFreePort();
        self::$server = new Process(['php', '-S', 'localhost:'.self::$port, '-t', self::$docroot]);
        self::$server->start();
        usleep(100000); // Give server time to start
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$server && self::$server->isRunning()) {
            self::$server->stop();
        }
        array_map('unlink', glob(self::$docroot.'/*.*'));
        rmdir(self::$docroot);
    }

    public function setUp(): void
    {
        $this->setUpPlaywright();
        $this->page->goto('http://localhost:'.self::$port.'/index.html');
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itGetsThePageUrl(): void
    {
        $this->assertEquals('http://localhost:'.self::$port.'/index.html', $this->page->url());
    }

    #[Test]
    public function itGetsThePageTitle(): void
    {
        $this->page->setContent('<title>My Title</title>');
        $this->assertEquals('My Title', $this->page->title());
    }

    #[Test]
    public function itCanNavigateBackAndForward(): void
    {
        $this->page->click('a');
        $this->page->waitForURL('**/page2.html');
        $this->assertStringContainsString('/page2.html', $this->page->url());

        $this->page->goBack();
        $this->page->waitForURL('**/index.html');
        $this->assertStringContainsString('/index.html', $this->page->url());

        $this->page->goForward();
        $this->page->waitForURL('**/page2.html');
        $this->assertStringContainsString('/page2.html', $this->page->url());
    }

    #[Test]
    public function itReloadsThePage(): void
    {
        // Get initial URL
        $initialUrl = $this->page->url();

        // Reload the page
        $this->page->reload();

        // Verify page reloaded successfully (URL should remain the same)
        $this->assertEquals($initialUrl, $this->page->url());
    }

    #[Test]
    public function itSetsTheViewportSize(): void
    {
        $this->page->setViewportSize(800, 600);
        $viewport = $this->page->viewportSize();
        $this->assertEquals(800, $viewport['width']);
        $this->assertEquals(600, $viewport['height']);
    }

    #[Test]
    public function itTakesAScreenshotAndReturnsPath(): void
    {
        $path = $this->page->screenshot();
        $this->assertIsString($path);
        $this->assertNotEmpty($path);

        // Verify the file was created
        $this->assertFileExists($path);

        // Verify it's a PNG file
        $fileContent = file_get_contents($path);
        $this->assertStringStartsWith(base64_decode('iVBORw0KGgo='), $fileContent);

        // Clean up
        unlink($path);
    }

    #[Test]
    public function itAddsAScriptTag(): void
    {
        $this->page->addScriptTag(['url' => '/script.js']);
        $this->assertEquals(123, $this->page->evaluate('window.myVar'));
    }

    #[Test]
    public function itAddsAStyleTag(): void
    {
        $this->page->addStyleTag(['url' => 'http://localhost:'.self::$port.'/style.css']);
        $this->page->waitForSelector('h1'); // Wait for the element to be available

        // Check if element exists first
        $count = $this->page->locator('h1')->count();
        $this->assertGreaterThan(0, $count, 'H1 element should exist');

        // Test simpler locator methods first
        $text = $this->page->locator('h1')->textContent();
        $this->assertEquals('Hello World', $text);

        $tagName = $this->page->locator('h1')->evaluate('element => element.tagName');
        $this->assertEquals('H1', $tagName);
        $color = $this->page->locator('h1')->evaluate('element => window.getComputedStyle(element).color');
        $this->assertEquals('rgb(255, 0, 0)', $color);
    }

    #[Test]
    public function itReturnsItsContext(): void
    {
        $this->assertSame($this->context, $this->page->context());
    }

    #[Test]
    public function itWaitsForAResponse(): void
    {
        $response = $this->page->waitForResponse(
            '**/page2.html',
            ['action' => "document.querySelector('a').click()"]
        );

        $this->assertInstanceOf(\PlaywrightPHP\Network\ResponseInterface::class, $response);
        $this->assertStringContainsString('/page2.html', $response->url());
        $this->assertEquals(200, $response->status());
    }

    private static function findFreePort(): int
    {
        $sock = socket_create_listen(0);
        socket_getsockname($sock, $addr, $port);
        socket_close($sock);

        return $port;
    }
}
