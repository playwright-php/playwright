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

namespace Playwright\Tests\Unit\Locator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Locator\RoleSelectorBuilder;

#[CoversClass(RoleSelectorBuilder::class)]
final class RoleSelectorBuilderTest extends TestCase
{
    #[Test]
    public function itBuildsBasicRoleSelector(): void
    {
        $selector = RoleSelectorBuilder::buildSelector('button');

        $this->assertSame('internal:role=button', $selector);
    }

    #[Test]
    public function itBuildsSelectorWithOptions(): void
    {
        $selector = RoleSelectorBuilder::buildSelector('heading', [
            'name' => 'Product Overview',
            'exact' => true,
            'level' => 2,
            'expanded' => false,
            'pressed' => 'mixed',
        ]);

        $this->assertSame(
            'internal:role=heading[name="Product Overview"][exact][expanded=false][pressed="mixed"][level=2]',
            $selector
        );
    }

    #[Test]
    public function itBuildsSelectorWithRegexName(): void
    {
        $selector = RoleSelectorBuilder::buildSelector('link', [
            'nameRegex' => '/^Docs?/i',
        ]);

        $this->assertSame('internal:role=link[name=/^Docs?/i]', $selector);
    }

    #[Test]
    public function itFiltersRoleSpecificOptions(): void
    {
        $options = [
            'name' => 'Submit',
            'includeHidden' => true,
            'has' => 'locator(.child)',
        ];

        $filtered = RoleSelectorBuilder::filterLocatorOptions($options);

        $this->assertSame(['has' => 'locator(.child)'], $filtered);
    }
}
