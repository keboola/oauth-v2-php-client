<?php
use Keboola\OAuthV2Api\Manager;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testListComponents()
    {
        $manager = new Manager(KBC_MANAGE_TOKEN);

        $result = $manager->listComponents();

        self::assertInternalType('array', $result);
    }

    /**
     * @expectedException \GuzzleHttp\Exception\ClientException
     * @expectedExceptionMessage Error validating Manage token: Invalid access token
     */
    public function testInvalidToken()
    {
        $manager = new Manager('invalid');

        $result = $manager->listComponents();
    }
}
