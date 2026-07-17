<?php

declare(strict_types=1);

namespace Keboola\OAuthV2Api;

use Closure;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;
use Keboola\ApiClientBase\ApiClient;
use Keboola\ApiClientBase\ApiClientOptions;
use Keboola\ApiClientBase\Auth\StorageApiTokenAuthenticator;
use Keboola\ApiClientBase\Json;
use Keboola\OAuthV2Api\Exception\ClientException;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

class Credentials
{
    private const FALLBACK_USER_AGENT = 'Keboola OAuth PHP Client';
    private const JSON_HEADERS = ['Content-Type' => 'application/json'];

    private ApiClient $apiClient;

    /**
     * @param non-empty-string $baseUrl
     * @param non-empty-string $storageToken
     * @param int<0, max> $backoffMaxTries
     */
    public function __construct(
        string $baseUrl,
        string $storageToken,
        ?LoggerInterface $logger = null,
        int $backoffMaxTries = ApiClientOptions::DEFAULT_BACKOFF_MAX_TRIES,
        int $connectTimeout = ApiClientOptions::DEFAULT_CONNECT_TIMEOUT,
        int $requestTimeout = ApiClientOptions::DEFAULT_REQUEST_TIMEOUT,
        string $userAgent = self::FALLBACK_USER_AGENT,
        null|Closure|HandlerStack $requestHandler = null,
    ) {
        Assert::stringNotEmpty($baseUrl, 'Base URL must be a non-empty string');

        $this->apiClient = new ApiClient(
            $baseUrl,
            new StorageApiTokenAuthenticator($storageToken),
            new ApiClientOptions(
                userAgent: $userAgent,
                backoffMaxTries: $backoffMaxTries,
                connectTimeout: $connectTimeout,
                requestTimeout: $requestTimeout,
                requestHandler: $requestHandler,
                logger: $logger,
            ),
            errorMessageResolver: new OAuthErrorMessageResolver(),
            exceptionClass: ClientException::class,
        );
    }

    /**
     * @return array<mixed>
     */
    public function getDetail(string $componentId, string $credentialsId): array
    {
        return $this->apiClient->sendRequestAndMapResponse(
            new Request(
                'GET',
                sprintf('credentials/%s/%s', rawurlencode($componentId), rawurlencode($credentialsId)),
            ),
            ArrayResponse::class,
        )->data;
    }

    /**
     * @return array<mixed>
     */
    public function listCredentials(string $componentId): array
    {
        return $this->apiClient->sendRequestAndMapResponse(
            new Request('GET', sprintf('credentials/%s', rawurlencode($componentId))),
            ArrayResponse::class,
        )->data;
    }

    public function delete(string $componentId, string $credentialsId): void
    {
        $this->apiClient->sendRequest(
            new Request(
                'DELETE',
                sprintf('credentials/%s/%s', rawurlencode($componentId), rawurlencode($credentialsId)),
            ),
        );
    }

    /**
     * @param array{id: string, authorizedFor: string, data: array<mixed>} $credentials
     * @return array<mixed>
     */
    public function add(string $componentId, array $credentials): array
    {
        $this->validateCredentials($credentials);

        return $this->apiClient->sendRequestAndMapResponse(
            new Request(
                'POST',
                sprintf('credentials/%s', rawurlencode($componentId)),
                self::JSON_HEADERS,
                Json::encodeArray($credentials),
            ),
            ArrayResponse::class,
        )->data;
    }

    /**
     * @param array<string, mixed> $credentials
     */
    protected function validateCredentials(array $credentials): void
    {
        foreach (['id', 'authorizedFor', 'data'] as $key) {
            if (empty($credentials[$key])) {
                throw new InvalidArgumentException("Missing key '{$key}'.");
            }
        }
    }
}
