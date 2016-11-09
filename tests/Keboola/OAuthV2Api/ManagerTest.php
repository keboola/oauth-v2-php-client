<?php

namespace Keboola\OAuthV2Api\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Keboola\OAuthV2Api\Common;
use Keboola\OAuthV2Api\Exception\RequestException;
use Keboola\OAuthV2Api\Manager;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testListComponents()
    {
        $mock = new MockHandler([
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
            )
        ]);

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $manager = new Manager('some-token', ['handler' => $stack, 'url' => 'https://sunar.keboola.com/oauth-v2/']);
        $result = $manager->listComponents();
        $this->assertCount(2, $result);

        /** @var Request $request */
        $request = $container[0]['request'];
        $this->assertEquals("https://sunar.keboola.com/oauth-v2/manage", $request->getUri()->__toString());
        $this->assertEquals("GET", $request->getMethod());
        $this->assertEquals("some-token", $request->getHeader("x-kbc-manageapitoken")[0]);
    }

    public function testInvalidToken()
    {
        $mock = new MockHandler([
            new Response(
                400,
                ['Content-Type' => 'application/json'],
                '{
                    "status": "error",
                    "error": "User error",
                    "code": 400,
                    "message": "Error validating Manage token: Invalid access token",
                    "exceptionId": "oauth-v2-1234",
                    "runId": 0
                 }'
            )
        ]);

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $manager = new Manager('some-token', ['handler' => $stack, 'url' => 'https://syrup.keboola.com/oauth-v2/']);
        try {
            $manager->listComponents();
            $this->fail("Invalid token must cause exception.");
        } catch (RequestException $e) {
            $this->assertContains('Invalid access token', $e->getMessage());
        }

        /** @var Request $request */
        $request = $container[0]['request'];
        $this->assertEquals("https://syrup.keboola.com/oauth-v2/manage", $request->getUri()->__toString());
        $this->assertEquals("GET", $request->getMethod());
        $this->assertEquals("some-token", $request->getHeader("x-kbc-manageapitoken")[0]);
    }

    public function testRetry()
    {
        $mock = new MockHandler([
            new Response(500, ['Content-Type' => 'application/json'], ''),
            new Response(500, ['Content-Type' => 'application/json'], ''),
            new Response(500, ['Content-Type' => 'application/json'], ''),
            new Response(500, ['Content-Type' => 'application/json'], ''),
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
            )
        ]);

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $manager = new Manager('some-token', ['handler' => $stack, 'url' => 'https://syrup.keboola.com/oauth-v2/']);
        $result = $manager->listComponents();
        $this->assertCount(2, $result);
    }

    public function testRetryFail()
    {
        $mock = new MockHandler([
            new Response(500, ['Content-Type' => 'application/json'], ''),
            new Response(500, ['Content-Type' => 'application/json'], ''),
            new Response(500, ['Content-Type' => 'application/json'], 'Really bad server error'),
        ]);

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $refl = new \ReflectionClass(Common::class);
        $prop = $refl->getProperty('backOffMaxRetries');
        $prop->setAccessible(true);
        $prop->setValue(2);
        $manager = new Manager('some-token', ['handler' => $stack, 'url' => 'https://syrup.keboola.com/oauth-v2/']);
        try {
            $manager->listComponents();
            $this->fail("Invalid token must cause exception.");
        } catch (RequestException $e) {
            $this->assertContains('Really bad server error', $e->getMessage());
        }
    }
}
