<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Locator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Locator\SelectorChain;

#[CoversClass(SelectorChain::class)]
final class SelectorChainTest extends TestCase
{
    public function testConstructorWithString(): void
    {
        $chain = new SelectorChain('.button');

        $this->assertEquals('.button', (string) $chain);
    }

    public function testAppend(): void
    {
        $chain = new SelectorChain('.parent');
        $chain->append('.child');

        $this->assertEquals('.parent >> .child', (string) $chain);
    }

    public function testAppendMultiple(): void
    {
        $chain = new SelectorChain('.grandparent');
        $chain->append('.parent');
        $chain->append('.child');

        $this->assertEquals('.grandparent >> .parent >> .child', (string) $chain);
    }

    public function testAppendWithComplexSelector(): void
    {
        $chain = new SelectorChain('div.container');
        $chain->append('button[data-testid="submit"]');

        $this->assertEquals('div.container >> button[data-testid="submit"]', (string) $chain);
    }

    public function testAppendWithEmptySelector(): void
    {
        $chain = new SelectorChain('.base');
        $chain->append('');

        // Current implementation doesn't ignore empty selectors
        $this->assertEquals('.base >> ', (string) $chain);
    }

    public function testClone(): void
    {
        $original = new SelectorChain('.original');
        $original->append('.child');

        $cloned = clone $original;
        $cloned->append('.grandchild');

        $this->assertEquals('.original >> .child', (string) $original);
        $this->assertEquals('.original >> .child >> .grandchild', (string) $cloned);
    }

    public function testChaining(): void
    {
        $chain = new SelectorChain('.container');

        $result = $chain->append('.item')->append('.link');

        $this->assertSame($chain, $result); // Should return same instance for chaining
        $this->assertEquals('.container >> .item >> .link', (string) $chain);
    }

    public function testWithSpecialCharacters(): void
    {
        $chain = new SelectorChain('[data-cy="test"]');
        $chain->append('text="Hello World"');

        $this->assertEquals('[data-cy="test"] >> text="Hello World"', (string) $chain);
    }

    public function testWithNthSelector(): void
    {
        $chain = new SelectorChain('.item');
        $chain->append('nth=2');

        $this->assertEquals('.item >> nth=2', (string) $chain);
    }
}
