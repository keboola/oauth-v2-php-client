<?php
namespace Keboola\OAuthV2Api;

use GuzzleHttp\Client;

class Credentials extends Common
{
    public function __construct($sapiToken)
    {
        $this->client = $this->getClient(['X-StorageApi-Token' => $sapiToken]);
    }

    /**
     * @param string $componentId
     * @param string $credentialsId
     * @return object
     */
    public function get($componentId, $credentialsId)
    {
        return $this->client->get("credentials/{$componentId}/{$credentialsId}");
    }

    /**
     * @param string $componentId
     * @return array
     */
    public function list($componentId)
    {
        return $this->client->get("credentials/{$componentId}");
    }

    /**
     * @param string $componentId
     * @param string $credentialsId
     */
    public function delete($componentId, $credentialsId)
    {
        return $this->client->delete("credentials/{$componentId}/{$credentialsId}");
    }
}
