<?php
declare(strict_types=1);

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
        $this->markTestSkipped('Interactive inspector is skipped in CI/sandbox. Run locally to verify.');

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
