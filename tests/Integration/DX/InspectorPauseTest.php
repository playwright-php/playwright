<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\DX;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\PlaywrightFactory;

#[CoversNothing]
class InspectorPauseTest extends TestCase
{
    #[Test]
    public function itOpensInspectorWhenPaused(): void
    {
        $this->markTestSkipped('Interactive inspector test requires manual intervention.');

        $config = new PlaywrightConfig(headless: false);
        $playwright = PlaywrightFactory::create($config);
        $browser = $playwright->chromium()->withInspector()->launch();
        $page = $browser->newPage();
        $page->goto('https://example.com');
        $page->pause();
        $browser->close();
        $this->addToAssertionCount(1);
    }
}
