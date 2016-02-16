<?php
use Keboola\OAuthV2Api\Common,
    Keboola\OAuthV2Api\Credentials;
use GuzzleHttp\Client,
    GuzzleHttp\Message\Response,
    GuzzleHttp\Stream\Stream,
    GuzzleHttp\Handler\MockHandler;

class CommonTest extends \PHPUnit_Framework_TestCase
{
    public function testOutputArray()
    {
        $body = '{"data": "value"}';

        $guzzle = new Client();

        $mock = new MockHandler([
            new Response(200, [], Stream::factory($body))
        ]);
        $guzzle->getEmitter()->attach($mock);

        $credentials = new Credentials('token');
        $object = $credentials->getDetail('component', 'credentials');
        self::assertInternalType('object', $object);

        $credentials->enableReturnArrays(true);
        $array = $credentials->getDetail('component', 'credentials');
        self::assertInternalType('array', $array);
    }

    protected static function callMethod($obj, $name, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }
}
