<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Support;

use Symfony\Component\Process\Process;

/**
 * Trait providing HTTP server functionality for integration tests.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
trait HttpServerTestTrait
{
    protected static ?Process $server = null;
    protected static string $docroot;
    protected static int $port;

    /**
     * Find a free port by creating a temporary socket.
     */
    protected static function findFreePort(): int
    {
        $sock = socket_create_listen(0);
        socket_getsockname($sock, $addr, $port);
        socket_close($sock);

        return $port;
    }

    /**
     * Start HTTP server with given content files.
     *
     * @param array<string, string> $files Array of filename => content pairs
     */
    protected static function startHttpServer(array $files = []): void
    {
        static::$docroot = sys_get_temp_dir().'/playwright-php-tests-'.uniqid('', true);
        mkdir(static::$docroot);

        // Create default files if none provided
        if (empty($files)) {
            $files = [
                'index.html' => '<h1>Test Server</h1>',
            ];
        }

        // Write files to docroot
        foreach ($files as $filename => $content) {
            file_put_contents(static::$docroot.'/'.$filename, $content);
        }

        static::$port = static::findFreePort();
        static::$server = new Process(['php', '-S', 'localhost:'.static::$port, '-t', static::$docroot]);
        static::$server->start();
        usleep(100_000); // Give server time to start
    }

    /**
     * Stop HTTP server and clean up files.
     */
    protected static function stopHttpServer(): void
    {
        if (static::$server && static::$server->isRunning()) {
            static::$server->stop();
        }

        if (isset(static::$docroot) && is_dir(static::$docroot)) {
            array_map('unlink', glob(static::$docroot.'/*.*'));
            rmdir(static::$docroot);
        }
    }

    /**
     * Get the base URL for the test server.
     */
    protected static function getServerUrl(string $path = ''): string
    {
        return 'http://localhost:'.static::$port.'/'.ltrim($path, '/');
    }
}
