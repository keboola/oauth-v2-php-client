<?php
namespace Keboola\OAuthV2Api;

use GuzzleHttp\Exception\RequestException,
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
        } catch(RequestException $e) {
            throw new
        }
    }
}
