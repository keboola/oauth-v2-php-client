<?php

namespace Keboola\OAuthV2Api;

/**
 *
 */
class Manager extends Common
{
    protected $requiredApiDetails = [
        "component_id",
        "friendly_name",
        "app_key",
        "app_secret",
        "auth_url",
        "token_url",
        "oauth_version"
    ];

    public function __construct($manageToken, $config = [])
    {
        $this->client = $this->getClient(['X-KBC-ManageApiToken' => $manageToken], $config);
    }

    /**
     * @param array $details ## more than that!
     * @return array|object
     */
    public function add(array $details)
    {
        $this->validateApiDetails($details);

        return $this->apiPost("manage", [
            'form_params' => $details
        ]);
    }

    /**
     * @param string $componentId
     */
    public function delete($componentId)
    {
        return $this->apiDelete("manage/{$componentId}");
    }

    /**
     * @param string $componentId
     * @return object
     */
    public function getDetail($componentId)
    {
        return $this->apiGet("manage/{$componentId}");
    }

    /**
     * @param $componentId
     * @param array $details
     * @return array|object
     */
    public function update($componentId, array $details)
    {
        return $this->apiPatch("manage/{$componentId}", [
            'form_params' => $details
        ]);
    }

    /**
     * @return array
     */
    public function listComponents()
    {
        return $this->apiGet("manage");
    }

    protected function validateApiDetails(array $details)
    {
        foreach ($this->requiredApiDetails as $key) {
            if (empty($details[$key])) {
                throw new \InvalidArgumentException("Missing key '{$key}'.");
            }
        }

        if ($details['oauth_version'] == 1.0 && empty($details['request_token_url'])) {
            throw new \InvalidArgumentException("Missing 'request_token_url' for OAuth 1.0");
        }
    }
}
