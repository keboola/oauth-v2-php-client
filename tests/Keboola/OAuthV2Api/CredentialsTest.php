<?php

namespace Keboola\OAuthV2Api\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Keboola\OAuthV2Api\Credentials;

class CredentialsTest extends \PHPUnit_Framework_TestCase
{
    public function testList()
    {
        $mock = new MockHandler([
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
            )
        ]);

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $cred = new Credentials('some-token', ['handler' => $stack]);
        $result = $cred->listCredentials('wr-dropbox');
        $this->assertInternalType('array', $result);
        $this->assertCount(1, $result);
        $this->assertObjectHasAttribute('authorizedFor', $result[0]);

        /** @var Request $request */
        $request = $container[0]['request'];
        $this->assertEquals(
            "https://syrup.keboola.com/oauth-v2/credentials/wr-dropbox",
            $request->getUri()->__toString()
        );
        $this->assertEquals("GET", $request->getMethod());
        $this->assertEquals("some-token", $request->getHeader("x-storageapi-token")[0]);
    }

    public function testDetailObject()
    {
        $mock = new MockHandler([
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
            )
        ]);

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $cred = new Credentials('some-token', ['handler' => $stack]);
        $result = $cred->getDetail('wr-dropbox', 'credentials-id');
        $this->assertInternalType('object', $result);
        $this->assertObjectHasAttribute('#appSecret', $result);
    }

    public function testDetailArray()
    {
        $mock = new MockHandler([
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
            )
        ]);

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $cred = new Credentials('some-token', ['handler' => $stack]);
        $cred->enableReturnArrays(true);
        $result = $cred->getDetail('wr-dropbox', 'credentials-id');
        $this->assertInternalType('array', $result);
        $this->assertCount(8, $result);
        $this->assertArrayHasKey('#data', $result);
        $this->assertArrayHasKey('#appSecret', $result);

        /** @var Request $request */
        $request = $container[0]['request'];
        $this->assertEquals(
            "https://syrup.keboola.com/oauth-v2/credentials/wr-dropbox/credentials-id",
            $request->getUri()->__toString()
        );
        $this->assertEquals("GET", $request->getMethod());
        $this->assertEquals("some-token", $request->getHeader("x-storageapi-token")[0]);
    }
}
