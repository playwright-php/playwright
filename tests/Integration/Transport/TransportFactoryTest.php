<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\Transport\JsonRpc\JsonRpcTransport;
use PlaywrightPHP\Transport\TransportFactory;
use Psr\Log\NullLogger;

#[CoversClass(TransportFactory::class)]
class TransportFactoryTest extends TestCase
{
    private TransportFactory $factory;
    private NullLogger $logger;

    public function setUp(): void
    {
        $this->factory = new TransportFactory();
        $this->logger = new NullLogger();
    }

    #[Test]
    public function itCanCreateTransportWithConfig(): void
    {
        $config = new PlaywrightConfig(
            nodePath: '/usr/bin/node',
            timeoutMs: 30000
        );

        $transport = $this->factory->create($config, $this->logger);

        $this->assertInstanceOf(JsonRpcTransport::class, $transport);
    }

    #[Test]
    public function itCanCreateTransportWithDefaultConfig(): void
    {
        $config = new PlaywrightConfig();
        $transport = $this->factory->create($config, $this->logger);

        $this->assertInstanceOf(JsonRpcTransport::class, $transport);
    }

    #[Test]
    public function itCanCreateTransportWithAsyncConfig(): void
    {
        $config = new PlaywrightConfig(
            nodePath: '/usr/bin/node'
        );
        $transport = $this->factory->create($config, $this->logger);

        $this->assertInstanceOf(JsonRpcTransport::class, $transport);
    }

    #[Test]
    public function itCanCreateTransportWithVerboseConfig(): void
    {
        $config = new PlaywrightConfig(
            nodePath: '/usr/bin/node'
        );
        $transport = $this->factory->create($config, $this->logger);

        $this->assertInstanceOf(JsonRpcTransport::class, $transport);
    }

    #[Test]
    public function itCanCreateTransportWithCustomTimeout(): void
    {
        $config = new PlaywrightConfig(
            timeoutMs: 60000
        );
        $transport = $this->factory->create($config, $this->logger);

        $this->assertInstanceOf(JsonRpcTransport::class, $transport);
    }
}
