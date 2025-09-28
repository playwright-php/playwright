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

require_once __DIR__.'/../../vendor/autoload.php';

use Playwright\Console\ConsoleMessage;
use Playwright\Playwright;

$browser = Playwright::chromium();
$page = $browser->newPage();

$page->events()->onConsole(static function (ConsoleMessage $message): void {
    $location = $message->location();
    $lineNumber = isset($location['lineNumber']) ? (int) $location['lineNumber'] : null;

    $suffix = null !== $lineNumber ? sprintf(' (line %d)', $lineNumber) : '';

    printf('[%s] %s%s'.PHP_EOL, strtoupper($message->type()), $message->text(), $suffix);
});

$html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<body>
<script>
    console.log('Hello from the page console');
    console.warn('Heads up! This is a warning message.');
    console.error('Something went wrong.');
</script>
</body>
</html>
HTML;

$page->goto('data:text/html,'.rawurlencode($html));

// Trigger an additional console message after navigation.
$page->evaluate("() => console.info('Additional info message from evaluate()')");

usleep(250000);

$page->close();
$browser->close();
