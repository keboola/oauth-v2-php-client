<?php

namespace Keboola\OAuthV2Api;

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

    /**
     * @param $componentId
     * @param array $credentials
     *  - id (string) - unique identifier
     *  - authorizedFor (string) - name of the owner of the credentials
     *  - data (array) - credentials data ie. access token
     * @return mixed
     */
    public function add($componentId, array $credentials)
    {
        $this->validateCredentials($credentials);
        return $this->apiPost("credentials/{$componentId}", [
            'form_params' => $credentials
        ]);
    }

    protected function validateCredentials(array $credentials)
    {
        foreach (['id', 'authorizedFor', 'data'] as $key) {
            if (empty($credentials[$key])) {
                throw new \InvalidArgumentException("Missing key '{$key}'.");
            }
        }
    }
}
