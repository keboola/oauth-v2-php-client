<?php
namespace Keboola\OAuthV2Api;

use GuzzleHttp\Client;

/**
 *
 */
class Manager extends Common
{
    public function __construct($manageToken)
    {
        $this->client = $this->getClient(['X-KBC-ManageApiToken' => $manageToken]);
    }

    /**
     * @param string $componentId ## more than that!
     * @param string $kbcToken ## TODO remove once docker encryption can do image encryption w/o user token
     */
    public function add($componentId, $kbcToken)
    {
        $this->client->post("manage", [
            'headers' => [
                'X-StorageApi-Token' => $kbcToken
            ],
            'form_params' => [
                
            ]
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
}
