<?php

declare(strict_types=1);

namespace Keboola\OAuthV2Api\Tests;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Keboola\ApiClientBase\Json;
use Keboola\OAuthV2Api\Credentials;
use Keboola\OAuthV2Api\Exception\ClientException;
use PHPUnit\Framework\TestCase;

class CredentialsTest extends TestCase
{
    use ApiClientTestTrait;

    private const BASE_URL = 'https://oauth.keboola.com';
    private const API_TOKEN = 'some-token';

    public function testListCredentials(): void
    {
        $requestHandler = self::createRequestHandler($requestsHistory, [
            new Response(200, ['Content-Type' => 'application/json'], Json::encodeArray([
                [
                    'authorizedFor' => 'test',
                    'id' => 'main',
                    'creator' => ['id' => '1234', 'description' => 'me@keboola.com'],
                    'created' => '2016-01-31 00:13:30',
                ],
            ])),
        ]);

        $client = new Credentials(self::BASE_URL, self::API_TOKEN, requestHandler: $requestHandler(...));

        $result = $client->listCredentials('wr-dropbox');

        self::assertCount(1, $result);
        self::assertIsArray($result[0]);
        self::assertArrayHasKey('authorizedFor', $result[0]);

        self::assertCount(1, $requestsHistory);
        self::assertRequestEquals(
            'GET',
            self::BASE_URL . '/credentials/wr-dropbox',
            ['X-StorageApi-Token' => self::API_TOKEN],
            null,
            $requestsHistory[0]['request'],
        );
    }

    public function testGetDetail(): void
    {
        $requestHandler = self::createRequestHandler($requestsHistory, [
            new Response(200, ['Content-Type' => 'application/json'], Json::encodeArray([
                'id' => 'main',
                'authorizedFor' => 'Myself',
                'creator' => ['id' => '1234', 'description' => 'me@keboola.com'],
                'created' => '2016-01-31 00:13:30',
                '#data' => 'KBC::ComponentProjectEncrypted==F2LdyHQB45lJHtf',
                'oauthVersion' => '2.0',
                'appKey' => '1234',
                '#appSecret' => 'KBC::ComponentEncrypted==/5fEM59+3+59+5+',
            ])),
        ]);

        $client = new Credentials(self::BASE_URL, self::API_TOKEN, requestHandler: $requestHandler(...));

        $result = $client->getDetail('wr-dropbox', 'credentials-id');

        self::assertCount(8, $result);
        self::assertArrayHasKey('#data', $result);
        self::assertArrayHasKey('#appSecret', $result);

        self::assertRequestEquals(
            'GET',
            self::BASE_URL . '/credentials/wr-dropbox/credentials-id',
            ['X-StorageApi-Token' => self::API_TOKEN],
            null,
            $requestsHistory[0]['request'],
        );
    }

    public function testAdd(): void
    {
        $requestHandler = self::createRequestHandler($requestsHistory, [
            new Response(201, ['Content-Type' => 'application/json'], Json::encodeArray([
                'id' => 'main',
                'authorizedFor' => 'Myself',
                '#data' => 'KBC::ComponentProjectEncrypted==F2LdyHQB45lJHtf',
                '#appSecret' => 'KBC::ComponentEncrypted==/5fEM59+3+59+5+',
            ])),
        ]);

        $client = new Credentials(self::BASE_URL, self::API_TOKEN, requestHandler: $requestHandler(...));

        $credentials = [
            'id' => 'main',
            'authorizedFor' => 'Myself',
            'data' => [
                'access_token' => 'something',
                'refresh_token' => 'something_else',
            ],
        ];
        $result = $client->add('wr-dropbox', $credentials);

        self::assertArrayHasKey('#data', $result);
        self::assertArrayHasKey('#appSecret', $result);

        self::assertRequestEquals(
            'POST',
            self::BASE_URL . '/credentials/wr-dropbox',
            [
                'Content-Type' => 'application/json',
                'X-StorageApi-Token' => self::API_TOKEN,
            ],
            Json::encodeArray($credentials),
            $requestsHistory[0]['request'],
        );
    }

    public function testDelete(): void
    {
        $requestHandler = self::createRequestHandler($requestsHistory, [
            new Response(204, [], ''),
        ]);

        $client = new Credentials(self::BASE_URL, self::API_TOKEN, requestHandler: $requestHandler(...));

        $client->delete('wr-dropbox', 'credentials-id');

        self::assertCount(1, $requestsHistory);
        self::assertRequestEquals(
            'DELETE',
            self::BASE_URL . '/credentials/wr-dropbox/credentials-id',
            ['X-StorageApi-Token' => self::API_TOKEN],
            null,
            $requestsHistory[0]['request'],
        );
    }

    public function testAddValidatesRequiredKeys(): void
    {
        $client = new Credentials(self::BASE_URL, self::API_TOKEN);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing key 'data'.");

        $client->add('wr-dropbox', ['id' => 'main', 'authorizedFor' => 'Myself', 'data' => []]);
    }

    public function testRetriesOnTransportErrorThenSucceeds(): void
    {
        $requestHandler = self::createRequestHandler($requestsHistory, [
            self::curlError(),
            self::curlError(),
            new Response(200, ['Content-Type' => 'application/json'], Json::encodeArray([
                'id' => 'main',
                'authorizedFor' => 'Myself',
            ])),
        ]);

        $client = new Credentials(self::BASE_URL, self::API_TOKEN, requestHandler: $requestHandler(...));

        $result = $client->getDetail('some-component', 'some-id');

        self::assertSame('main', $result['id']);
    }

    public function testRetriesExhaustedThrowsClientException(): void
    {
        $requestHandler = self::createRequestHandler($requestsHistory, [
            self::curlError(),
            self::curlError(),
            self::curlError(),
        ]);

        $client = new Credentials(
            self::BASE_URL,
            self::API_TOKEN,
            backoffMaxTries: 2,
            requestHandler: $requestHandler(...),
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('cURL error 56');

        $client->getDetail('some-component', 'some-id');
    }

    public function testEmptyBaseUrlThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Base URL must be a non-empty string');

        new Credentials('', self::API_TOKEN); // @phpstan-ignore-line
    }

    public function testEmptyTokenThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Storage API token must not be empty');

        new Credentials(self::BASE_URL, ''); // @phpstan-ignore-line
    }

    private static function curlError(): RequestException
    {
        return new RequestException(
            'cURL error 56: OpenSSL SSL_read: Connection reset by peer, errno 104',
            new Request('GET', 'https://example.com'),
            null,
            null,
            ['errno' => 56],
        );
    }
}
