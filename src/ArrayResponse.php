<?php

declare(strict_types=1);

namespace Keboola\OAuthV2Api;

use Keboola\ApiClientBase\ResponseModelInterface;

/**
 * Minimal response model that carries the decoded response body as-is.
 *
 * The base ApiClient only returns void or a {@see ResponseModelInterface}; this shim lets the
 * OAuth clients keep exposing the raw decoded array as their public API. `$data` is typed as a
 * generic array so it accepts both object responses (credential/component detail) and list
 * responses (listCredentials / listComponents).
 */
final class ArrayResponse implements ResponseModelInterface
{
    /**
     * @param array<mixed> $data
     */
    public function __construct(
        public readonly array $data,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromResponseData(array $data): static
    {
        return new self($data);
    }
}
