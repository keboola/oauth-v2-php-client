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
    private const DEFAULT_HEADERS = ['Accept' => 'application/json'];
    private const JSON_HEADERS = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];

    // Preserve the behaviour of the legacy Common-based client (do not fall back to base defaults).
    private const DEFAULT_BACKOFF_MAX_TRIES = 10;
    private const DEFAULT_CONNECT_TIMEOUT = 120;
    private const DEFAULT_REQUEST_TIMEOUT = 120;

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
        int $backoffMaxTries = self::DEFAULT_BACKOFF_MAX_TRIES,
        int $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT,
        int $requestTimeout = self::DEFAULT_REQUEST_TIMEOUT,
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
                self::DEFAULT_HEADERS,
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
            new Request('GET', sprintf('credentials/%s', rawurlencode($componentId)), self::DEFAULT_HEADERS),
            ArrayResponse::class,
        )->data;
    }

    public function delete(string $componentId, string $credentialsId): void
    {
        $this->apiClient->sendRequest(
            new Request(
                'DELETE',
                sprintf('credentials/%s/%s', rawurlencode($componentId), rawurlencode($credentialsId)),
                self::DEFAULT_HEADERS,
            ),
        );
    }

    /**
     * @param array{id: string, authorizedFor: string, "#data": string} $credentials
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
        foreach (['id', 'authorizedFor', '#data'] as $key) {
            if (empty($credentials[$key])) {
                throw new InvalidArgumentException("Missing key '{$key}'.");
            }
        }
    }
}
