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

namespace Playwright\Tests\Integration\JSHandle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\JSHandle\JSHandle;
use Playwright\JSHandle\JSHandleInterface;
use Playwright\Testing\PlaywrightTestCaseTrait;

#[CoversClass(JSHandle::class)]
class JSHandleTest extends TestCase
{
    use PlaywrightTestCaseTrait;

    public function setUp(): void
    {
        $this->setUpPlaywright();
        $this->page->setContent('<div id="target" data-value="42">Target</div>');
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itReadsTheJsonValueOfAHandle(): void
    {
        $handle = $this->page->evaluateHandle('() => ({ answer: 42, nested: { ok: true } })');

        $this->assertSame(['answer' => 42, 'nested' => ['ok' => true]], $handle->jsonValue());
        $handle->dispose();
    }

    #[Test]
    public function itEvaluatesAgainstTheHandledValue(): void
    {
        $handle = $this->page->evaluateHandle('() => ({ answer: 42 })');

        $this->assertSame(43, $handle->evaluate('(value) => value.answer + 1'));
        $this->assertSame(52, $handle->evaluate('(value, arg) => value.answer + arg', 10));
        $handle->dispose();
    }

    #[Test]
    public function itChainsIntoAnotherHandle(): void
    {
        $handle = $this->page->evaluateHandle('() => ({ answer: 42 })');

        $child = $handle->evaluateHandle('(value) => ({ doubled: value.answer * 2 })');
        $this->assertInstanceOf(JSHandleInterface::class, $child);
        $this->assertSame(['doubled' => 84], $child->jsonValue());

        $child->dispose();
        $handle->dispose();
    }

    #[Test]
    public function itReadsPropertiesOneByOneAndAsAMap(): void
    {
        $handle = $this->page->evaluateHandle('() => ({ answer: 42, label: "meaning" })');

        $this->assertSame(42, $handle->getProperty('answer')->jsonValue());

        $properties = $handle->getProperties();
        $this->assertArrayHasKey('answer', $properties);
        $this->assertArrayHasKey('label', $properties);
        $this->assertSame('meaning', $properties['label']->jsonValue());

        $handle->dispose();
    }

    #[Test]
    public function itDistinguishesElementHandlesFromPlainValues(): void
    {
        $element = $this->page->locator('#target')->evaluateHandle('(node) => node');
        $this->assertInstanceOf(JSHandleInterface::class, $element->asElement());

        $plain = $this->page->evaluateHandle('() => ({ answer: 42 })');
        $this->assertNull($plain->asElement());

        $element->dispose();
        $plain->dispose();
    }
}
