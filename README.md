#OAuth PHP Client

[![Build Status](https://travis-ci.org/keboola/oauth-v2-php-client.svg?branch=master)](https://travis-ci.org/keboola/oauth-v2-php-client)
[![Code Climate](https://codeclimate.com/github/keboola/oauth-v2-bundle/badges/gpa.svg)](https://codeclimate.com/github/keboola/oauth-v2-bundle)
[![Test Coverage](https://codeclimate.com/github/keboola/oauth-v2-bundle/badges/coverage.svg)](https://codeclimate.com/github/keboola/oauth-v2-bundle/coverage)

## Usage examples

Table write:

```php
require 'vendor/autoload.php';

use Keboola\OAuthV2Api\Credentials;

$credentials = new Credentials(
  'YOUR_TOKEN',
  [
    'url' => 'https://syrup.keboola.com/oauth-v2/',
  ]
);
$result = $credentials->listCredentials('keboola.ex-google-drive');
```

