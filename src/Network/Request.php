<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Network;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class Request implements RequestInterface
{
    public function __construct(
        private readonly array $data,
    ) {
    }

    public function url(): string
    {
        return $this->data['url'];
    }

    public function method(): string
    {
        return $this->data['method'];
    }

    public function headers(): array
    {
        return $this->data['headers'];
    }

    public function postData(): ?string
    {
        return $this->data['postData'];
    }

    public function postDataJSON(): ?array
    {
        $postData = $this->postData();
        if (null === $postData) {
            return null;
        }

        return json_decode($postData, true);
    }

    public function resourceType(): string
    {
        return $this->data['resourceType'];
    }
}
