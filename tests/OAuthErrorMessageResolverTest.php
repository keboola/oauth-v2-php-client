<?php

declare(strict_types=1);

namespace Keboola\OAuthV2Api\Tests;

use Keboola\OAuthV2Api\OAuthErrorMessageResolver;
use PHPUnit\Framework\TestCase;

class OAuthErrorMessageResolverTest extends TestCase
{
    public function testReturnsPrefixedMessageFromJsonBody(): void
    {
        $resolver = new OAuthErrorMessageResolver();
        self::assertSame(
            'OAuth API error: Something failed',
            $resolver('{"message":"Something failed"}', 400),
        );
    }

    public function testReturnsNullForNonJsonBody(): void
    {
        $resolver = new OAuthErrorMessageResolver();
        self::assertNull($resolver('not json at all', 500));
    }

    public function testReturnsNullWhenNoMessageField(): void
    {
        $resolver = new OAuthErrorMessageResolver();
        self::assertNull($resolver('{"error":"User error"}', 400));
    }
}
