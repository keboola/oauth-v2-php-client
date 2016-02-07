<?php
use Keboola\OAuthV2Api\Credentials;

class CredentialsTest extends \PHPUnit_Framework_TestCase
{
    public function testList()
    {
        $cred = new Credentials(STORAGE_API_TOKEN);

        $result = $cred->listCredentials('wr-dropbox');

        self::assertInternalType('array', $result);
    }
}
