<?php

declare(strict_types=1);

namespace Keboola\OAuthV2Api\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Keboola\OAuthV2Api\Exception\ClientException;
use Keboola\OAuthV2Api\Manager;
use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    public function testListComponents(): void
    {
        $mock = new MockHandler(
            [
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
            ]
        );

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $manager = new Manager(
            'some-token',
            ['handler' => $stack, 'url' => 'https://sunar.keboola.com/oauth-v2/']
        );
        $result = $manager->listComponents();
        self::assertCount(2, $result);

        /** @var Request $request */
        $request = $container[0]['request'];
        self::assertSame('https://sunar.keboola.com/oauth-v2/manage', $request->getUri()->__toString());
        self::assertSame('GET', $request->getMethod());
        self::assertSame('some-token', $request->getHeader('x-kbc-manageapitoken')[0]);
    }

    public function testUpdateComponent(): void
    {
        $mock = new MockHandler(
            [
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{
                    "id": "ex-dropbox",
                    "friendly_name": "Dropbox Extractor",
                    "app_key": "1234",
                    "oauth_version": "2.0"
                }'
            ),
            ]
        );

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $manager = new Manager(
            'some-token',
            ['handler' => $stack, 'url' => 'https://sunar.keboola.com/oauth-v2/']
        );
        $manager->update('ex-dropbox', ['friendly_name' => 'Dropbox Extractor 2']);

        /** @var Request $request */
        $request = $container[0]['request'];
        $this->assertSame(
            'https://sunar.keboola.com/oauth-v2/manage/ex-dropbox',
            $request->getUri()->__toString()
        );
        $this->assertSame('PATCH', $request->getMethod());
        $this->assertSame('some-token', $request->getHeader('x-kbc-manageapitoken')[0]);
    }

    public function testCreateComponent(): void
    {
        $mock = new MockHandler(
            [
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{
                    "id": "ex-dropbox",
                    "friendly_name": "Dropbox Extractor",
                    "app_key": "1234",
                    "oauth_version": "2.0"
                }'
            ),
            ]
        );

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $manager = new Manager(
            'some-token',
            ['handler' => $stack, 'url' => 'https://sunar.keboola.com/oauth-v2/']
        );
        $details = [
            'component_id' => 'ex-dropbox',
            'friendly_name' => 'Dropbox Extractor 2',
            'app_key' => 'test',
            'app_secret' => 'test',
            'auth_url' => 'test',
            'token_url' => 'test',
            'oauth_version' => '2.0',
        ];
        $manager->add($details);

        /** @var Request $request */
        $request = $container[0]['request'];
        $this->assertSame('https://sunar.keboola.com/oauth-v2/manage', $request->getUri()->__toString());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('some-token', $request->getHeader('x-kbc-manageapitoken')[0]);
    }

    public function testInvalidToken(): void
    {
        $mock = new MockHandler(
            [
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
            ),
            ]
        );

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $manager = new Manager(
            'some-token',
            ['handler' => $stack, 'url' => 'https://syrup.keboola.com/oauth-v2/']
        );
        try {
            $manager->listComponents();
            self::fail('Invalid token must cause exception.');
        } catch (ClientException $e) {
            self::assertStringContainsString('Invalid access token', $e->getMessage());
        }

        /** @var Request $request */
        $request = $container[0]['request'];
        self::assertSame('https://syrup.keboola.com/oauth-v2/manage', $request->getUri()->__toString());
        self::assertSame('GET', $request->getMethod());
        self::assertSame('some-token', $request->getHeader('x-kbc-manageapitoken')[0]);
    }

    public function testRetry(): void
    {
        $mock = new MockHandler(
            [
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
                ),
            ]
        );

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $manager = new Manager(
            'some-token',
            ['handler' => $stack, 'url' => 'https://syrup.keboola.com/oauth-v2/']
        );
        $result = $manager->listComponents();
        self::assertCount(2, $result);
    }

    public function testRetryFail(): void
    {
        $mock = new MockHandler(
            [
            new Response(500, ['Content-Type' => 'application/json'], ''),
            new Response(500, ['Content-Type' => 'application/json'], ''),
            new Response(500, ['Content-Type' => 'application/json'], 'Really bad server error'),
            ]
        );

        // Add the history middleware to the handler stack.
        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $manager = new Manager(
            'some-token',
            ['handler' => $stack, 'url' => 'https://syrup.keboola.com/oauth-v2/', 'backoffMaxTries' => 2]
        );
        try {
            $manager->listComponents();
            self::fail('Invalid token must cause exception.');
        } catch (ClientException $e) {
            self::assertStringContainsString('Really bad server error', $e->getMessage());
        }
    }
}
