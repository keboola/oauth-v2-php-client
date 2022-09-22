<?php

declare(strict_types=1);

namespace Keboola\OAuthV2Api;

use InvalidArgumentException;

class Manager extends Common
{
    private const REQUIRED_API_DETAILS = [
        'component_id',
        'friendly_name',
        'app_key',
        'app_secret',
        'auth_url',
        'token_url',
        'oauth_version',
    ];

    public function __construct(string $manageToken, array $config)
    {
        parent::__construct(['X-KBC-ManageApiToken' => $manageToken], $config);
    }

    public function add(array $details): array
    {
        $this->validateApiDetails($details);
        return $this->apiPost('manage', $details);
    }

    public function delete(string $componentId): void
    {
        $this->apiDelete(sprintf('manage/%s', $componentId));
    }

    public function getDetail(string $componentId): array
    {
        return $this->apiGet(sprintf('manage/%s', $componentId));
    }

    public function update(string $componentId, array $details): array
    {
        return $this->apiPatch(sprintf('manage/%s', $componentId), $details);
    }

    public function listComponents(): array
    {
        return $this->apiGet('manage');
    }

    protected function validateApiDetails(array $details): void
    {
        foreach (self::REQUIRED_API_DETAILS as $key) {
            if (empty($details[$key])) {
                throw new InvalidArgumentException("Missing key '{$key}'.");
            }
        }

        if ($details['oauth_version'] === '1.0' && empty($details['request_token_url'])) {
            throw new InvalidArgumentException("Missing 'request_token_url' for OAuth 1.0");
        }
    }
}
