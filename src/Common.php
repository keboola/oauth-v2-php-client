<?php

declare(strict_types=1);

namespace Keboola\OAuthV2Api;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use JsonException;
use Keboola\OAuthV2Api\Exception\ClientException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class Common
{
    protected Client $client;
    private const DEFAULT_BACKOFF_RETRIES = 10;
    private const DEFAULT_HEADERS = ['Accept' => 'application/json'];

    /**
     * @param array{url: string, handler?: HandlerStack, backoffMaxTries?: int} $config
     * @throws ClientException
     */
    public function __construct(array $headers, array $config)
    {
        // Initialize handlers (start with those supplied in constructor)
        $handlerStack = $config['handler'] ?? HandlerStack::create();
        $handlerStack->push(
            Middleware::retry(
                self::createDefaultDecider($config['backoffMaxTries'] ?? self::DEFAULT_BACKOFF_RETRIES)
            )
        );

        $this->client = new Client(
            [
                'base_uri' => $config['url'],
                'headers' => array_merge(
                    $headers,
                    self::DEFAULT_HEADERS
                ),
                'handler' => $handlerStack,
            ]
        );
    }

    protected function apiGet(string $url): array
    {
        $request = new Request('GET', $url);
        return $this->sendRequest($request);
    }

    protected function apiDelete(string $url): void
    {
        $request = new Request('DELETE', $url);
        $this->sendRequest($request);
    }

    protected function apiPost(string $url, array $body): array
    {
        $request = new Request('POST', $url, [], json_encode($body, JSON_THROW_ON_ERROR));
        return $this->sendRequest($request);
    }

    protected function apiPatch(string $url, array $body): array
    {
        $request = new Request('PATCH', $url, [], json_encode($body, JSON_THROW_ON_ERROR));
        return $this->sendRequest($request);
    }

    protected function sendRequest(Request $request): array
    {
        try {
            $response = $this->client->send($request);
            $body = $response->getBody()->getContents();
            if ($body === '') {
                return [];
            }
            return (array) json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (RequestException $e) {
            $message = $e->getMessage();
            if ($e->getResponse()) {
                try {
                    $response = (array) json_decode(
                        $e->getResponse()->getBody()->getContents(),
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );
                    if (!empty($response['message'])) {
                        $message = $response['message'];
                    }
                } catch (Throwable $e2) {
                }
            }
            throw new ClientException('OAuth API error: ' . $message, $e->getCode(), $e);
        } catch (GuzzleException $e) {
            throw new ClientException('OAuth API error: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (JsonException $e) {
            throw new ClientException('Unable to parse response body into JSON: ' . $e->getMessage());
        }
    }
    private static function createDefaultDecider(int $maxRetries): Closure
    {
        return function (
            int $retries,
            RequestInterface $request,
            ?ResponseInterface $response = null,
            ?Throwable $error = null
        ) use ($maxRetries) {
            if ($retries >= $maxRetries) {
                return false;
            } elseif ($response && $response->getStatusCode() >= 500) {
                return true;
            } elseif ($error && $error->getCode() >= 500) {
                return true;
            } else {
                return false;
            }
        };
    }
}
