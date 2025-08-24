<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\Node;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Node\Exception\NodeBinaryNotFoundException;
use PlaywrightPHP\Node\Exception\NodeVersionTooLowException;
use PlaywrightPHP\Node\NodeBinaryResolver;
use PlaywrightPHP\Tests\Mocks\TestLogger;

#[CoversClass(NodeBinaryResolver::class)]
class NodeBinaryResolverTest extends TestCase
{
    #[Test]
    public function itCanBeInstantiated(): void
    {
        $resolver = new NodeBinaryResolver();

        $this->assertInstanceOf(NodeBinaryResolver::class, $resolver);
    }

    #[Test]
    public function itResolvesNodeFromSystem(): void
    {
        $resolver = new NodeBinaryResolver();

        $nodePath = $resolver->resolve();

        $this->assertIsString($nodePath);
        $this->assertNotEmpty($nodePath);
        $this->assertFileExists($nodePath);
    }

    #[Test]
    public function itReturnsVersionForNodeBinary(): void
    {
        $resolver = new NodeBinaryResolver();
        $nodePath = $resolver->resolve();

        $version = $resolver->getVersion($nodePath);

        $this->assertIsString($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $version);
    }

    #[Test]
    public function itAcceptsNodePath(): void
    {
        $systemResolver = new NodeBinaryResolver();
        $systemNodePath = $systemResolver->resolve();

        $resolver = new NodeBinaryResolver($systemNodePath);
        $resolvedPath = $resolver->resolve();

        $this->assertEquals($systemNodePath, $resolvedPath);
    }

    #[Test]
    public function itCachesResolvedPath(): void
    {
        $resolver = new NodeBinaryResolver();

        $path1 = $resolver->resolve();
        $path2 = $resolver->resolve();

        $this->assertEquals($path1, $path2);
    }

    #[Test]
    public function itThrowsExceptionForNonexistentPath(): void
    {
        $resolver = new NodeBinaryResolver(
            '/nonexistent/path/to/node',
            '18.0.0',
            null,
            [],
            true
        );

        $this->expectException(NodeBinaryNotFoundException::class);
        $this->expectExceptionMessage('Explicit Node.js binary not found or not executable');
        $resolver->resolve();
    }

    #[Test]
    public function itValidatesMinimumVersion(): void
    {
        $systemResolver = new NodeBinaryResolver();
        $systemNodePath = $systemResolver->resolve();

        $resolver = new NodeBinaryResolver($systemNodePath, '99.99.99');

        $this->expectException(NodeVersionTooLowException::class);
        $resolver->resolve();
    }

    #[Test]
    public function itLogsDebugMessages(): void
    {
        $logger = new TestLogger();
        $resolver = new NodeBinaryResolver(null, '18.0.0', $logger);

        $resolver->resolve();

        $this->assertGreaterThan(0, count($logger->records));

        $messages = array_column($logger->records, 'message');
        $this->assertStringContainsString('Resolved Node:', implode(' ', $messages));
    }

    #[Test]
    public function itHandlesInvalidVersionOutput(): void
    {
        $resolver = new NodeBinaryResolver();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unexpected Node version output');

        $resolver->getVersion('/bin/echo');
    }

    #[Test]
    public function itSupportsAdditionalSearchPaths(): void
    {
        $systemResolver = new NodeBinaryResolver();
        $systemNodePath = $systemResolver->resolve();
        $nodeDir = dirname($systemNodePath);

        $resolver = new NodeBinaryResolver(
            null,
            '18.0.0',
            null,
            [$nodeDir]
        );

        $resolvedPath = $resolver->resolve();

        $this->assertFileExists($resolvedPath);
    }
}
