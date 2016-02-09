<?php
namespace Keboola\OAuthV2Api;

use GuzzleHttp\Exception\RequestException as GuzzleException,
    GuzzleHttp\Client;

class ClientWrapper
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function __call($name, $arguments)
    {
        try {
            return call_user_func_array([$this->client, $name], $arguments);
        } catch(GuzzleException $e) {
            throw new Exception\RequestException("Error communicating with OAuth API: " . $e->getMessage(), 0, $e);
        }
    }
}
