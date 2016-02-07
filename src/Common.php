<?php
namespace Keboola\OAuthV2Api;

use GuzzleHttp\Exception\RequestException,
    GuzzleHttp\HandlerStack,
    GuzzleHttp\Middleware,
    GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface,
    Psr\Http\Message\ResponseInterface;
use Keboola\Utils\Utils;

/**
 * @todo
 * getClient here that creates the retry etc
 *  - accepts default headers as parameter
 * apiUrl
 */
class Common
{
    /**
     * @var string
     */
    protected $apiUrl = 'https://syrup.keboola.com/oauth-v2/';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var int
     */
    protected $backoffMaxTries = 10;

    /**
     * @var array
     */
    protected $defaultHeaders = [
        'Accept' => 'application/json'
    ];

    /**
     * @param array $headers
     * @return Client
     */
    protected function getClient(array $headers)
    {
        $client = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => array_merge(
                $headers,
                $this->defaultHeaders
            )
        ]);

        $handlerStack = HandlerStack::create();
        $handlerStack->push(Middleware::retry(
            self::createDefaultDecider($this->backoffMaxTries),
            self::createExponentialDelay()
        ));

        return $client;
    }

    protected function apiGet($url)
    {
        return Utils::json_decode($this->client->get($url)->getBody());
    }

    /**
     * @todo check code = 204?
     */
    protected function apiDelete($url)
    {
        return $this->client->delete($url)->getBody();
    }

    protected function apiPost($url, $options)
    {
        return Utils::json_decode($this->client->post($url, $options));
    }

    /**
     * @todo Lib to wrap this
     */
    private static function createDefaultDecider($maxRetries = 3)
    {
        return function (
            $retries,
            RequestInterface $request,
            ResponseInterface $response = null,
            $error = null
        ) use ($maxRetries) {
            if ($retries >= $maxRetries) {
                return false;
            } elseif ($response && $response->getStatusCode() > 499) {
                return true;
            } elseif ($error) {
                return true;
            } else {
                return false;
            }
        };
    }

    private static function createExponentialDelay()
    {
        return function ($retries) {
            return (int)pow(2, $retries - 1) * 1000;
        };
    }
}
