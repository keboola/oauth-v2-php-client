<?php
namespace Keboola\OAuthV2Api;

use GuzzleHttp\Client;

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

    public function __construct($manageToken)
    {
        $this->client = $this->getClient(['X-KBC-ManageApiToken' => $manageToken]);
    }

    /**
     * @param array $details ## more than that!
     * @param string $kbcToken ## TODO remove once docker encryption can do image encryption w/o user token
     */
    public function add(array $details, $kbcToken)
    {
        $this->validateApiDetails($details);

        $this->client->post("manage", [
            'headers' => [
                'X-StorageApi-Token' => $kbcToken
            ],
            'form_params' => $details
        ]);
    }

    /**
     * @param string $componentId
     */
    public function delete($componentId)
    {
        $this->client->delete("manage/{$componentId}");
    }

    /**
     * @param string $componentId
     * @return object
     */
    public function getDetail($componentId)
    {
        $this->client->get("manage/{$componentId}");
    }

    /**
     * @return array
     */
    public function listComponents()
    {
        $this->client->get("manage");
    }

    protected function validateApiDetails(array $details)
    {
        foreach($this->requiredApiDetails as $key) {
            if (empty($details[$key])) {
                throw new \InvalidArgumentException("Missing key '{$key}'.");
            }
        }

        if ($details['oauth_version'] == 1.0 && empty($details['request_token_url'])) {
            throw new \InvalidArgumentException("Missing 'request_token_url' for OAuth 1.0");
        }
    }
}
