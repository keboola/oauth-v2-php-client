<?php
namespace Keboola\OAuthV2Api;

use GuzzleHttp\Client;

class Credentials extends Common
{
    public function __construct($sapiToken, $config = [])
    {
        $this->client = $this->getClient(['X-StorageApi-Token' => $sapiToken], $config);
    }

    /**
     * @param string $componentId
     * @param string $credentialsId
     * @return object|array
     */
    public function getDetail($componentId, $credentialsId)
    {
        return $this->apiGet("credentials/{$componentId}/{$credentialsId}");
    }

    /**
     * @param string $componentId
     * @return array
     */
    public function listCredentials($componentId)
    {
        return $this->apiGet("credentials/{$componentId}");
    }

    /**
     * @param string $componentId
     * @param string $credentialsId
     */
    public function delete($componentId, $credentialsId)
    {
        return $this->apiDelete("credentials/{$componentId}/{$credentialsId}");
    }
}
