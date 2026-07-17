<?php

declare(strict_types=1);

namespace Keboola\OAuthV2Api\Tests;

use Keboola\OAuthV2Api\ArrayResponse;
use PHPUnit\Framework\TestCase;

class ArrayResponseTest extends TestCase
{
    public function testWrapsDecodedData(): void
    {
        $data = ['id' => 'main', 'nested' => [1, 2, 3]];
        $response = ArrayResponse::fromResponseData($data);
        self::assertSame($data, $response->data);
    }

    public function testWrapsListData(): void
    {
        $data = [['id' => 'a'], ['id' => 'b']];
        $response = ArrayResponse::fromResponseData($data);
        self::assertSame($data, $response->data);
    }
}
