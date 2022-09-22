# OAuth PHP Client

[![Build Status](https://travis-ci.org/keboola/oauth-v2-php-client.svg?branch=master)](https://travis-ci.org/keboola/oauth-v2-php-client)

## Usage examples

### List credentials

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


## License

MIT licensed, see [LICENSE](./LICENSE) file.
