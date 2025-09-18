<?php

$license = <<<LICENSE
This file is part of the community-maintained Playwright PHP project.
It is not affiliated with or endorsed by Microsoft.

(c) 2025-Present - Playwright PHP - https://github.com/playwright-php

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
LICENSE;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__);

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
        'header_comment' => ['header' => $license],
    ])
;
