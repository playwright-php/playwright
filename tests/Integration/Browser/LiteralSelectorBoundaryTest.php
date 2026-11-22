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

namespace Playwright\Tests\Integration\Browser;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Playwright\Network\RouteInterface;
use Playwright\Testing\PlaywrightTestCaseTrait;

#[CoversNothing]
final class LiteralSelectorBoundaryTest extends TestCase
{
    use PlaywrightTestCaseTrait;

    protected function setUp(): void
    {
        $this->setUpPlaywright();
        $this->page->route('https://private.example/**', static function (RouteInterface $route): void {
            $route->fulfill(['contentType' => 'text/html', 'headers' => ['access-control-allow-origin' => 'https://private.example'], 'body' => '<p>private-content</p><input id="password">']);
        });
    }

    protected function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[DataProvider('frameNames')]
    public function testQuotedFrameNamesKeepOperationsInTheSelectedFrame(string $name): void
    {
        $this->page->setContent('<iframe name="'.htmlspecialchars($name, ENT_QUOTES).'" src="https://private.example/frame"></iframe><iframe id="attacker" srcdoc="&lt;input id=password&gt;"></iframe>');
        $this->page->evaluate('() => { CSS.escape = value => value; Element.prototype.getAttribute = () => "attacker"; }');
        $frame = $this->page->frame(['url' => 'https://private.example/frame']);
        $this->assertNotNull($frame);
        $frame->locator('#password')->fill('frame-value');
        $this->assertSame('frame-value', $frame->locator('#password')->inputValue());
        $this->assertSame('', $this->page->frameLocator('#attacker')->locator('#password')->inputValue());
    }

    public function testUrlSelectedFramesKeepTheirIdentityAfterAttributesChange(): void
    {
        $this->page->setContent('<iframe name="trusted" src="https://private.example/frame"></iframe><iframe id="attacker" srcdoc="&lt;input id=password&gt;"></iframe>');
        $frame = $this->page->frame(['url' => 'https://private.example/frame']);
        $this->assertNotNull($frame);
        $this->page->evaluate('() => { document.querySelector("iframe").name = "renamed"; document.querySelector("#attacker").name = "trusted"; }');
        $frame->locator('#password')->fill('original-frame');
        $this->assertSame('original-frame', $frame->locator('#password')->inputValue());
        $this->assertSame('', $this->page->frameLocator('#attacker')->locator('#password')->inputValue());
        $this->assertNotNull($frame->parentFrame());
        $frame->owner()->contentFrame()->first()->locator('#password')->fill('original-owner');
        $this->assertSame('original-owner', $frame->locator('#password')->inputValue());
        $this->page->setDefaultTimeout(100);
        $this->assertSame(0, $frame->owner()->contentFrame()->nth(1)->locator('#password')->count());
    }

    public function testNativeFrameReferencesCanReportDetachment(): void
    {
        $this->page->setContent('<iframe name="target" srcdoc="&lt;p&gt;frame&lt;/p&gt;"></iframe>');
        $frame = $this->page->frame(['name' => 'target']);
        $this->assertNotNull($frame);
        $this->assertFalse($frame->isDetached());
        $this->page->evaluate('() => document.querySelector("iframe").remove()');
        $this->assertTrue($frame->isDetached());
    }

    #[DataProvider('literalValues')]
    public function testLiteralLocatorsAndFiltersKeepTheirScope(string $value): void
    {
        $escaped = htmlspecialchars($value, ENT_QUOTES);
        $markup = '<section><img alt="'.$escaped.'"><input placeholder="'.$escaped.'"><p data-testid="'.$escaped.'" title="'.$escaped.'">'.$escaped.'</p></section><p id="unrelated">outside</p>';
        $this->page->setContent($markup.'<iframe id="literal" name="literal" srcdoc="'.htmlspecialchars($markup, ENT_QUOTES).'"></iframe>');
        $frame = $this->page->frame(['name' => 'literal']);
        $this->assertNotNull($frame);
        $scopes = [$this->page, $this->page->locator('section'), $frame, $this->page->frameLocator('#literal')];

        foreach ($scopes as $scope) {
            $this->assertSame(1, $scope->getByTestId($value)->count());
            $this->assertSame(1, $scope->getByAltText($value)->count());
            $this->assertSame(1, $scope->getByPlaceholder($value)->count());
            $this->assertSame(1, $scope->getByTitle($value)->count());
            $this->assertSame(1, $scope->getByText($value)->count());
        }

        $this->assertSame(1, $this->page->locator('section p')->filter(['hasText' => $value])->count());
        $this->assertSame(0, $this->page->locator('section p')->filter(['hasNotText' => $value])->count());
        $this->assertSame(0, $this->page->locator('section p')->filter(['hasText' => '"), #unrelated, :has-text("missing'])->count());
    }

    /** @return iterable<string, array{string}> */
    public static function frameNames(): iterable
    {
        yield 'selector list' => ['"], iframe#attacker, iframe[name="'];
        yield 'chain delimiter' => ['frame >> quoted " value'];
    }

    /** @return iterable<string, array{string}> */
    public static function literalValues(): iterable
    {
        yield 'selector list' => ['"), #unrelated, :has-text("'];
        yield 'whitespace and escapes' => ["quoted\\ value\n >> café"];
    }

    public function testNestedAnonymousFramesKeepTheirNativeParent(): void
    {
        $inner = '<input id="nested">';
        $outer = '<iframe name="target" srcdoc="'.htmlspecialchars($inner, ENT_QUOTES).'"></iframe>';
        $this->page->setContent('<iframe srcdoc="'.htmlspecialchars($outer, ENT_QUOTES).'"></iframe>');
        $frame = $this->page->frame(['name' => 'target']);
        $this->assertNotNull($frame);
        $frame->locator('#nested')->fill('nested-value');
        $this->assertSame('nested-value', $frame->locator('#nested')->inputValue());
        $this->assertCount(1, $this->page->mainFrame()->childFrames()[0]->childFrames());
    }

    #[DataProvider('frameIds')]
    public function testNonIdentifierFrameIdsRemainAddressable(string $id): void
    {
        $inner = '<input id="target">';
        $this->page->setContent('<iframe id="'.htmlspecialchars($id, ENT_QUOTES).'" name="target" srcdoc="'.htmlspecialchars($inner, ENT_QUOTES).'"></iframe>');
        $frame = $this->page->frame(['name' => 'target']);
        $this->assertNotNull($frame);
        $frame->locator('#target')->fill('id-value');
        $this->assertSame('id-value', $frame->locator('#target')->inputValue());
    }

    /** @return iterable<string, array{string}> */
    public static function frameIds(): iterable
    {
        yield 'hyphen digit' => ['-1'];
        yield 'hyphen digit word' => ['-9foo'];
        yield 'leading digit' => ['1frame'];
        yield 'quoted delimiter' => ['id >> quoted " value'];
    }
}
