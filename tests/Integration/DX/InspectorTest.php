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
use Symfony\Component\Process\ExecutableFinder;

#[CoversNothing]
class InspectorTest extends TestCase
{
    private function getNodeExecutable(): string
    {
        $finder = new ExecutableFinder();
        $node = $finder->find('node');
        if (null === $node) {
            $this->markTestSkipped('Node.js executable not found.');
        }

        return $node;
    }

    #[Test]
    public function itLaunchesWithInspector(): void
    {
        $config = new PlaywrightConfig(nodePath: $this->getNodeExecutable());
        $playwright = PlaywrightFactory::create($config);
        // Test that withInspector() method works and browser launches successfully
        $browser = $playwright->chromium()->withInspector()->launch();
        $context = $browser->newContext();

        // Verify browser launched successfully with inspector
        $this->assertInstanceOf('PlaywrightPHP\\Browser\\Browser', $browser);
        $this->assertInstanceOf('PlaywrightPHP\\Browser\\BrowserContext', $context);

        $context->close();
        $browser->close();
    }
}
