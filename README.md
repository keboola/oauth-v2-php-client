# OAuth PHP Client

[![GitHub Actions](https://github.com/keboola/oauth-v2-php-client/actions/workflows/push.yml/badge.svg)](https://github.com/keboola/oauth-v2-php-client/actions/workflows/push.yml)

## Usage examples

### List credentials

```php
require 'vendor/autoload.php';

use Keboola\OAuthV2Api\Credentials;

$credentials = new Credentials(
  'YOUR_TOKEN',
  [
    'url' => 'https://oauth.keboola.com/',
  ]
);
$result = $credentials->listCredentials('keboola.ex-google-drive');
```

## License

MIT licensed, see [LICENSE](./LICENSE) file.
