<?php

declare(strict_types=1);

namespace Keboola\OAuthV2Api\Exception;

use Keboola\ApiClientBase\Exception\ClientException as BaseClientException;

/**
 * Thrown on any failed OAuth API request.
 *
 * Subclasses the base exception so callers can catch this service-specific type while still
 * benefiting from the base context accessors ({@see getStatusCode()}, {@see getResponseBody()}).
 */
class ClientException extends BaseClientException
{
}
