<?php

namespace Keboola\OAuthV2Api\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Keboola\OAuthV2Api\Credentials;

class CommonTest extends \PHPUnit_Framework_TestCase
{
    public function testListComponents()
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

        $cred = new Credentials('some-token');
        $result = $cred->listCredentials('wr-dropbox');
        $this->assertInternalType('array', $result);
        $this->assertCount(4, $result);
        $this->assertArrayHasKey('authorizedFor', $result);

        /** @var Request $request */
        $request = $container[0]['request'];
        $this->assertEquals(
            "https://syrup.keboola.com/oauth-v2/credentials/wr-dropbox",
            $request->getUri()->__toString()
        );
        $this->assertEquals("GET", $request->getMethod());
        $this->assertEquals("some-token", $request->getHeader("x-storageapi-token")[0]);
    }


    public function testOutputArray()
    {
        $body = '{"data": "value"}';

        $guzzle = new Client();

        $mock = new MockHandler([
            new Response(200, [], new Stream($body))
        ]);
        $guzzle->getEmitter()->attach($mock);

        $credentials = new Credentials('token');
        $object = $credentials->getDetail('component', 'credentials');
        self::assertInternalType('object', $object);

        $credentials->enableReturnArrays(true);
        $array = $credentials->getDetail('component', 'credentials');
        self::assertInternalType('array', $array);
    }    
}
