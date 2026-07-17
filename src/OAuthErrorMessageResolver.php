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

        if (empty($data['message']) || !is_string($data['message'])) {
            return null;
        }

        return 'OAuth API error: ' . $data['message'];
    }
}
