<?php

declare(strict_types=1);

namespace Keboola\OAuthV2Api;

use JsonException;
use Keboola\ApiClientBase\ErrorMessageResolverInterface;
use Keboola\ApiClientBase\Json;

final class OAuthErrorMessageResolver implements ErrorMessageResolverInterface
{
    public function __invoke(string $responseBody, int $statusCode): ?string
    {
        try {
            $data = Json::decodeArray($responseBody);
        } catch (JsonException) {
            return null;
        }

        // oauth-service puts the reason in "error"; "message" takes precedence when both are set
        foreach (['message', 'error'] as $field) {
            if (!empty($data[$field]) && is_string($data[$field])) {
                return 'OAuth API error: ' . $data[$field];
            }
        }

        return null;
    }
}
