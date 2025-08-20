<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Exception;

/**
 * Thrown when a protocol-level error occurs during communication with the browser.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class ProtocolErrorException extends PlaywrightException
{
    public function __construct(
        string $message,
        int $code,
        private readonly ?string $protocolName = null,
        private readonly ?string $method = null,
        private readonly mixed $params = null,
        private readonly ?string $remoteStack = null,
    ) {
        $context = [
            'protocolName' => $this->protocolName,
            'method' => $this->method,
            'params' => $this->params,
            'remoteStack' => $this->remoteStack,
        ];

        parent::__construct($message, $code, null, array_filter($context, static fn ($v) => null !== $v));
    }

    public function getProtocolName(): ?string
    {
        return $this->protocolName;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function getParams(): mixed
    {
        return $this->params;
    }

    public function getRemoteStack(): ?string
    {
        return $this->remoteStack;
    }
}
