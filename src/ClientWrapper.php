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
        } catch (GuzzleException $e) {
            $message = $e->getMessage();
            if ($e->getResponse() && $e->getResponse()->getBody()) {
                try {
                    $response = \Keboola\Utils\jsonDecode($e->getResponse()->getBody(), true);
                    if (!empty($response['message'])) {
                        $message = $response['message'];
                    }
                } catch (\Exception $e) {
                }
            }
            throw new Exception\RequestException("OAuth API error: " . $message, $e->getCode(), $e);
        }
    }
}
