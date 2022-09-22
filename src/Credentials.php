<?php

declare(strict_types=1);

namespace Keboola\OAuthV2Api;

use InvalidArgumentException;

class Credentials extends Common
{
    public function __construct(string $sapiToken, array $config)
    {
        parent::__construct(['X-StorageApi-Token' => $sapiToken], $config);
    }

    public function getDetail(string $componentId, string $credentialsId): array
    {
        return $this->apiGet(sprintf('credentials/%s/%s', $componentId, $credentialsId));
    }

    public function listCredentials(string $componentId): array
    {
        return $this->apiGet(sprintf('credentials/%s', $componentId));
    }

    public function delete(string $componentId, string $credentialsId): void
    {
        $this->apiDelete(sprintf('credentials/%s/%s', $componentId, $credentialsId));
    }

    /**
     * @param array{
     *  'id': string,
     *  'authorizedFor': string,
     *  'data': array
     * } $credentials
     */
    public function add(string $componentId, array $credentials): array
    {
        $this->validateCredentials($credentials);
        return $this->apiPost(sprintf('credentials/%s', $componentId), $credentials);
    }

    protected function validateCredentials(array $credentials): void
    {
        foreach (['id', 'authorizedFor', 'data'] as $key) {
            if (empty($credentials[$key])) {
                throw new InvalidArgumentException("Missing key '{$key}'.");
            }
        }
    }
}
