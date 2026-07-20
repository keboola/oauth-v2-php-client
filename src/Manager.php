<?php

declare(strict_types=1);

namespace Keboola\OAuthV2Api;

use Closure;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;
use Keboola\ApiClientBase\ApiClient;
use Keboola\ApiClientBase\ApiClientOptions;
use Keboola\ApiClientBase\Auth\KeboolaServiceAccountAuthenticator;
use Keboola\ApiClientBase\Auth\ManageApiTokenAuthenticator;
use Keboola\ApiClientBase\Json;
use Keboola\OAuthV2Api\Exception\ClientException;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

class Manager
{
    private const FALLBACK_USER_AGENT = 'Keboola OAuth Manager PHP Client';
    private const DEFAULT_HEADERS = ['Accept' => 'application/json'];
    private const JSON_HEADERS = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];

    // Preserve the behaviour of the legacy Common-based client (do not fall back to base defaults).
    private const DEFAULT_BACKOFF_MAX_TRIES = 10;
    private const DEFAULT_CONNECT_TIMEOUT = 120;
    private const DEFAULT_REQUEST_TIMEOUT = 120;

    private const REQUIRED_API_DETAILS = [
        'component_id',
        'friendly_name',
        'app_key',
        'app_secret',
        'auth_url',
        'token_url',
        'oauth_version',
    ];

    private ApiClient $apiClient;

    /**
     * @param non-empty-string $baseUrl
     * @param non-empty-string|null $manageToken
     * @param int<0, max> $backoffMaxTries
     *
     * When $manageToken is provided, authenticates with X-KBC-ManageApiToken.
     * When null (default), authenticates via the projected Kubernetes ServiceAccount
     * token — see {@see KeboolaServiceAccountAuthenticator}.
     */
    public function __construct(
        string $baseUrl,
        ?string $manageToken = null,
        ?LoggerInterface $logger = null,
        int $backoffMaxTries = self::DEFAULT_BACKOFF_MAX_TRIES,
        int $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT,
        int $requestTimeout = self::DEFAULT_REQUEST_TIMEOUT,
        string $userAgent = self::FALLBACK_USER_AGENT,
        null|Closure|HandlerStack $requestHandler = null,
    ) {
        Assert::stringNotEmpty($baseUrl, 'Base URL must be a non-empty string');

        $authenticator = $manageToken !== null
            ? new ManageApiTokenAuthenticator($manageToken)
            : new KeboolaServiceAccountAuthenticator();

        $this->apiClient = new ApiClient(
            $baseUrl,
            $authenticator,
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
     * @param array<string, mixed> $details
     * @return array<mixed>
     */
    public function add(array $details): array
    {
        $this->validateApiDetails($details);

        return $this->apiClient->sendRequestAndMapResponse(
            new Request('POST', 'manage', self::JSON_HEADERS, Json::encodeArray($details)),
            ArrayResponse::class,
        )->data;
    }

    public function delete(string $componentId): void
    {
        $this->apiClient->sendRequest(
            new Request('DELETE', sprintf('manage/%s', rawurlencode($componentId)), self::DEFAULT_HEADERS),
        );
    }

    /**
     * @return array<mixed>
     */
    public function getDetail(string $componentId): array
    {
        return $this->apiClient->sendRequestAndMapResponse(
            new Request('GET', sprintf('manage/%s', rawurlencode($componentId)), self::DEFAULT_HEADERS),
            ArrayResponse::class,
        )->data;
    }

    /**
     * @param array<string, mixed> $details
     * @return array<mixed>
     */
    public function update(string $componentId, array $details): array
    {
        return $this->apiClient->sendRequestAndMapResponse(
            new Request(
                'PATCH',
                sprintf('manage/%s', rawurlencode($componentId)),
                self::JSON_HEADERS,
                Json::encodeArray($details),
            ),
            ArrayResponse::class,
        )->data;
    }

    /**
     * @return array<mixed>
     */
    public function listComponents(): array
    {
        return $this->apiClient->sendRequestAndMapResponse(
            new Request('GET', 'manage', self::DEFAULT_HEADERS),
            ArrayResponse::class,
        )->data;
    }

    /**
     * @param array<string, mixed> $details
     */
    protected function validateApiDetails(array $details): void
    {
        foreach (self::REQUIRED_API_DETAILS as $key) {
            if (empty($details[$key])) {
                throw new InvalidArgumentException("Missing key '{$key}'.");
            }
        }

        if ($details['oauth_version'] === '1.0' && empty($details['request_token_url'])) {
            throw new InvalidArgumentException("Missing 'request_token_url' for OAuth 1.0");
        }
    }
}
