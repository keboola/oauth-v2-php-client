<?php

declare(strict_types=1);

namespace Keboola\OAuthV2Api\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Keboola\OAuthV2Api\Credentials;
use PHPUnit\Framework\TestCase;

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
