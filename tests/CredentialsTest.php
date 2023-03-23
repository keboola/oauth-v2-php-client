<?php

declare(strict_types=1);

namespace Keboola\OAuthV2Api\Tests;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Keboola\OAuthV2Api\Credentials;
use Keboola\OAuthV2Api\Exception\ClientException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class CredentialsTest extends TestCase
{
    public function testList(): void
    {
        $mock = new MockHandler(
            [
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '[
                    {
                        "authorizedFor": "test",
                        "id": "main",
                        "creator": {
                            "id": "1234",
                            "description": "me@keboola.com"
                        },
                        "created": "2016-01-31 00:13:30"
                    }
                ]'
            ),
            ]
        );

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $cred = new Credentials('some-token', ['handler' => $stack, 'url' => 'https://jezevec.keboola.com/oauth-v2/']);
        $result = $cred->listCredentials('wr-dropbox');
        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertArrayHasKey('authorizedFor', $result[0]);

        /** @var Request $request */
        $request = $container[0]['request'];
        self::assertSame(
            'https://jezevec.keboola.com/oauth-v2/credentials/wr-dropbox',
            $request->getUri()->__toString()
        );
        self::assertSame('GET', $request->getMethod());
        self::assertSame('some-token', $request->getHeader('x-storageapi-token')[0]);
    }

    public function testRetryCurlExceptionFail(): void
    {
        $mock = new MockHandler(
            [
                new Response(500, ['Content-Type' => 'application/json'], 'not used'),
                new Response(500, ['Content-Type' => 'application/json'], 'not used'),
                new Response(500, ['Content-Type' => 'application/json'], 'not used'),
            ],
            function (ResponseInterface $a) {
                // abusing the mockhandler here: override the mock response and throw a Request exception
                throw new RequestException(
                    'OAuth API error: cURL error 56: OpenSSL SSL_read: Connection reset by peer, errno 104',
                    new Request('GET', 'https://example.com'),
                    null,
                    null,
                    [
                        'errno' => 56,
                        'error' => 'OpenSSL SSL_read: Connection reset by peer, errno 104',
                    ]
                );
            }
        );

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $credentials = new Credentials(
            'some-token',
            ['handler' => $stack, 'url' => 'https://oauth.keboola.com', 'backoffMaxTries' => 2]
        );
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('OAuth API error: OAuth API error: cURL error 56:');
        $credentials->getDetail('some-component', 'some-id');
    }

    public function testRetryCurlException(): void
    {
        $mock = new MockHandler(
            [
                new Response(500, ['Content-Type' => 'application/json'], 'not used'),
                new Response(500, ['Content-Type' => 'application/json'], 'not used'),
                new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    '[
                        {
                            "id": "ex-dropbox",
                            "friendly_name": "Dropbox Extractor",
                            "app_key": "1234",
                            "oauth_version": "2.0"
                        },
                        {
                            "id": "wr-dropbox",
                            "friendly_name": "Dropbox Writer",
                            "app_key": "5678",
                            "oauth_version": "2.0"
                        }
                    ]'
                ),
            ],
            function (ResponseInterface $a) {
                if ($a->getStatusCode() === 500) {
                    // abusing the mockhandler here: override the mock response and throw a Request exception
                    throw new RequestException(
                        'OAuth API error: cURL error 56: OpenSSL SSL_read: Connection reset by peer, errno 104',
                        new Request('GET', 'https://example.com'),
                        null,
                        null,
                        [
                            'errno' => 56,
                            'error' => 'OpenSSL SSL_read: Connection reset by peer, errno 104',
                        ]
                    );
                }
            }
        );

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $credentials = new Credentials(
            'some-token',
            ['handler' => $stack, 'url' => 'https://oauth.keboola.com']
        );
        $result = $credentials->getDetail('some-component', 'some-id');
        self::assertCount(2, $result);
    }

    public function testRetryCurlExceptionWithoutContext(): void
    {
        $mock = new MockHandler(
            [
                new Response(500, ['Content-Type' => 'application/json'], 'not used')
            ],
            function (ResponseInterface $a) {
                // abusing the mockhandler here: override the mock response and throw a Request exception
                throw new RequestException(
                    'OAuth API error: cURL error 56: OpenSSL SSL_read: Connection reset by peer, errno 104',
                    new Request('GET', 'https://example.com'),
                    null,
                    null,
                    []
                );
            }
        );

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $credentials = new Credentials(
            'some-token',
            ['handler' => $stack, 'url' => 'https://oauth.keboola.com']
        );
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('OAuth API error: OAuth API error: cURL error 56:');
        $credentials->getDetail('some-component', 'some-id');
    }

    public function testDetailArray(): void
    {
        $mock = new MockHandler(
            [
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{
                    "id": "main",
                    "authorizedFor": "Myself",
                    "creator": {
                        "id": "1234",
                        "description": "me@keboola.com"
                    },
                    "created": "2016-01-31 00:13:30",
                    "#data": "KBC::ComponentProjectEncrypted==F2LdyHQB45lJHtf",
                    "oauthVersion": "2.0",
                    "appKey": "1234",
                    "#appSecret": "KBC::ComponentEncrypted==/5fEM59+3+59+5+"
                }'
            ),
            ]
        );

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $cred = new Credentials(
            'some-token',
            ['handler' => $stack, 'url' => 'https://syrup.keboola.com/oauth-v2/']
        );
        $result = $cred->getDetail('wr-dropbox', 'credentials-id');
        self::assertIsArray($result);
        self::assertCount(8, $result);
        self::assertArrayHasKey('#data', $result);
        self::assertArrayHasKey('#appSecret', $result);

        /** @var Request $request */
        $request = $container[0]['request'];
        self::assertSame(
            'https://syrup.keboola.com/oauth-v2/credentials/wr-dropbox/credentials-id',
            $request->getUri()->__toString()
        );
        self::assertSame('GET', $request->getMethod());
        self::assertSame('some-token', $request->getHeader('x-storageapi-token')[0]);
    }

    public function testAdd(): void
    {
        $mock = new MockHandler(
            [
            new Response(
                201,
                ['Content-Type' => 'application/json'],
                '{
                    "id": "main",
                    "authorizedFor": "Myself",
                    "creator": {
                        "id": "1234",
                        "description": "me@keboola.com"
                    },
                    "created": "2016-01-31 00:13:30",
                    "#data": "KBC::ComponentProjectEncrypted==F2LdyHQB45lJHtf",
                    "oauthVersion": "2.0",
                    "appKey": "1234",
                    "#appSecret": "KBC::ComponentEncrypted==/5fEM59+3+59+5+"
                }'
            ),
            ]
        );

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $cred = new Credentials('some-token', ['handler' => $stack, 'url' => 'https://boo']);
        $result = $cred->add(
            'wr-dropbox',
            [
            'id' => 'main',
            'authorizedFor' => 'Myself',
            'data' => [
                'access_token' => 'something',
                'refresh_token' => 'something_else',
            ],
            ]
        );
        self::assertIsArray($result);
        self::assertCount(8, $result);
        self::assertArrayHasKey('#data', $result);
        self::assertArrayHasKey('#appSecret', $result);

        /** @var Request $request */
        $request = $container[0]['request'];
        self::assertSame(
            'https://boo/credentials/wr-dropbox',
            $request->getUri()->__toString()
        );
        self::assertSame('POST', $request->getMethod());
        self::assertSame('some-token', $request->getHeader('x-storageapi-token')[0]);
    }
}
