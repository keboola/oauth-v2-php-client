<?php

declare(strict_types=1);

namespace Keboola\OAuthV2Api\Tests;

use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Keboola\ApiClientBase\Json;
use Keboola\OAuthV2Api\Exception\ClientException;
use Keboola\OAuthV2Api\Manager;
use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    use ApiClientTestTrait;

    private const BASE_URL = 'https://oauth.keboola.com';
    private const API_TOKEN = 'some-token';

    public function testListComponents(): void
    {
        $requestHandler = self::createRequestHandler($requestsHistory, [
            new Response(200, ['Content-Type' => 'application/json'], Json::encodeArray([
                [
                    'id' => 'ex-dropbox',
                    'friendly_name' => 'Dropbox Extractor',
                    'app_key' => '1234',
                    'oauth_version' => '2.0',
                ],
                [
                    'id' => 'wr-dropbox',
                    'friendly_name' => 'Dropbox Writer',
                    'app_key' => '5678',
                    'oauth_version' => '2.0',
                ],
            ])),
        ]);

        $client = new Manager(self::BASE_URL, self::API_TOKEN, requestHandler: $requestHandler(...));

        $result = $client->listComponents();

        self::assertCount(2, $result);
        self::assertRequestEquals(
            'GET',
            self::BASE_URL . '/manage',
            ['X-KBC-ManageApiToken' => self::API_TOKEN],
            null,
            $requestsHistory[0]['request'],
        );
    }

    public function testUpdate(): void
    {
        $requestHandler = self::createRequestHandler($requestsHistory, [
            new Response(200, ['Content-Type' => 'application/json'], Json::encodeArray([
                'id' => 'ex-dropbox',
                'friendly_name' => 'Dropbox Extractor',
                'app_key' => '1234',
                'oauth_version' => '2.0',
            ])),
        ]);

        $client = new Manager(self::BASE_URL, self::API_TOKEN, requestHandler: $requestHandler(...));

        $client->update('ex-dropbox', ['friendly_name' => 'Dropbox Extractor 2']);

        self::assertRequestEquals(
            'PATCH',
            self::BASE_URL . '/manage/ex-dropbox',
            [
                'Content-Type' => 'application/json',
                'X-KBC-ManageApiToken' => self::API_TOKEN,
            ],
            Json::encodeArray(['friendly_name' => 'Dropbox Extractor 2']),
            $requestsHistory[0]['request'],
        );
    }

    public function testCreate(): void
    {
        $requestHandler = self::createRequestHandler($requestsHistory, [
            new Response(200, ['Content-Type' => 'application/json'], Json::encodeArray([
                'id' => 'ex-dropbox',
                'friendly_name' => 'Dropbox Extractor',
                'app_key' => '1234',
                'oauth_version' => '2.0',
            ])),
        ]);

        $client = new Manager(self::BASE_URL, self::API_TOKEN, requestHandler: $requestHandler(...));

        $details = [
            'component_id' => 'ex-dropbox',
            'friendly_name' => 'Dropbox Extractor 2',
            'app_key' => 'test',
            'app_secret' => 'test',
            'auth_url' => 'test',
            'token_url' => 'test',
            'oauth_version' => '2.0',
        ];
        $client->add($details);

        self::assertRequestEquals(
            'POST',
            self::BASE_URL . '/manage',
            [
                'Content-Type' => 'application/json',
                'X-KBC-ManageApiToken' => self::API_TOKEN,
            ],
            Json::encodeArray($details),
            $requestsHistory[0]['request'],
        );
    }

    public function testCreateValidatesRequiredKeys(): void
    {
        $client = new Manager(self::BASE_URL, self::API_TOKEN);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing key 'app_key'.");

        $client->add([
            'component_id' => 'ex-dropbox',
            'friendly_name' => 'Dropbox Extractor',
            'app_key' => '',
            'app_secret' => 'test',
            'auth_url' => 'test',
            'token_url' => 'test',
            'oauth_version' => '2.0',
        ]);
    }

    public function testCreateOauth1RequiresRequestTokenUrl(): void
    {
        $client = new Manager(self::BASE_URL, self::API_TOKEN);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing 'request_token_url' for OAuth 1.0");

        $client->add([
            'component_id' => 'ex-twitter',
            'friendly_name' => 'Twitter',
            'app_key' => 'test',
            'app_secret' => 'test',
            'auth_url' => 'test',
            'token_url' => 'test',
            'oauth_version' => '1.0',
        ]);
    }

    public function testInvalidTokenUsesOAuthErrorMessage(): void
    {
        $requestHandler = self::createRequestHandler($requestsHistory, [
            new Response(400, ['Content-Type' => 'application/json'], Json::encodeArray([
                'status' => 'error',
                'error' => 'User error',
                'code' => 400,
                'message' => 'Error validating Manage token: Invalid access token',
                'exceptionId' => 'oauth-v2-1234',
                'runId' => 0,
            ])),
        ]);

        $client = new Manager(
            self::BASE_URL,
            self::API_TOKEN,
            backoffMaxTries: 0,
            requestHandler: $requestHandler(...),
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('OAuth API error: Error validating Manage token: Invalid access token');

        $client->listComponents();
    }

    public function testRetriesOnServerErrorThenSucceeds(): void
    {
        $requestHandler = self::createRequestHandler($requestsHistory, [
            new Response(500, ['Content-Type' => 'application/json'], ''),
            new Response(500, ['Content-Type' => 'application/json'], ''),
            new Response(200, ['Content-Type' => 'application/json'], Json::encodeArray([
                [
                    'id' => 'ex-dropbox',
                    'friendly_name' => 'Dropbox Extractor',
                    'app_key' => '1234',
                    'oauth_version' => '2.0',
                ],
            ])),
        ]);

        $client = new Manager(self::BASE_URL, self::API_TOKEN, requestHandler: $requestHandler(...));

        $result = $client->listComponents();
        self::assertCount(1, $result);
    }

    public function testRetriesExhaustedThrowsClientException(): void
    {
        $requestHandler = self::createRequestHandler($requestsHistory, [
            new Response(500, ['Content-Type' => 'application/json'], ''),
            new Response(500, ['Content-Type' => 'application/json'], ''),
            new Response(500, ['Content-Type' => 'application/json'], 'Really bad server error'),
        ]);

        $client = new Manager(
            self::BASE_URL,
            self::API_TOKEN,
            backoffMaxTries: 2,
            requestHandler: $requestHandler(...),
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Really bad server error');

        $client->listComponents();
    }
}
