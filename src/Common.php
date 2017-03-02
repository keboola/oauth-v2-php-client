<?php
namespace Keboola\OAuthV2Api;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class Common
{
    /**
     * @var ClientWrapper
     */
    protected $client;

    /**
     * @var int
     */
    protected static $backOffMaxRetries = 10;

    /**
     * @var array
     */
    protected $defaultHeaders = [
        'Accept' => 'application/json'
    ];

    /**
     * @var bool
     */
    protected $returnArrays = false;

    /**
     * @param array $headers
     * @param array $config
     * @return Client
     */
    protected function getClient(array $headers, array $config = [])
    {
        if (!isset($config['url'])) {
            throw new \Exception('url is required. e.g. https://syrup.keboola.com/oauth-v2/');
        }

        // Initialize handlers (start with those supplied in constructor)
        if (isset($config['handler']) && is_a($config['handler'], HandlerStack::class)) {
            $handlerStack = HandlerStack::create($config['handler']);
        } else {
            $handlerStack = HandlerStack::create();
        }
        $handlerStack->push(Middleware::retry(
            self::createDefaultDecider(self::$backOffMaxRetries),
            self::createExponentialDelay()
        ));

        $client = new Client([
            'base_uri' => $config['url'],
            'headers' => array_merge(
                $headers,
                $this->defaultHeaders
            ),
            'handler' => $handlerStack
        ]);
        return new ClientWrapper($client);
    }

    protected function apiGet($url)
    {
        $response = $this->client->get($url)->getBody();
        return \Keboola\Utils\jsonDecode($response, $this->returnArrays);
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
        $response = $this->client->post($url, $options)->getBody();
        return \Keboola\Utils\jsonDecode($response, $this->returnArrays);
    }

    protected function apiPatch($url, $options)
    {
        $response = $this->client->patch($url, $options)->getBody();
        return \Keboola\Utils\jsonDecode($response, $this->returnArrays);
    }

    /**
     * @todo Lib to wrap this
     * @param $maxRetries
     * @return \Closure
     */
    private static function createDefaultDecider($maxRetries)
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
            } elseif ($error && $error->getCode() > 499) {
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

    /**
     * @param bool $enable
     */
    public function enableReturnArrays($enable)
    {
        $this->returnArrays = (bool) $enable;
    }
}
