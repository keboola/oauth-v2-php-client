# OAuth v2 PHP Client

[![GitHub Actions](https://github.com/keboola/oauth-v2-php-client/actions/workflows/push.yml/badge.svg)](https://github.com/keboola/oauth-v2-php-client/actions/workflows/push.yml)

Keboola OAuth v2 API client, built on
[`keboola/php-api-client-base`](https://github.com/keboola/php-api-client-base).

## Installation

```bash
composer require keboola/oauth-v2-php-client
```

## Usage

Both clients take the API base URL and the auth token first, followed by optional,
named transport options (`logger`, `backoffMaxTries`, `connectTimeout`,
`requestTimeout`, `userAgent`, `requestHandler`).

### Credentials (Storage API token)

```php
use Keboola\OAuthV2Api\Credentials;

$credentials = new Credentials(
    'https://oauth.keboola.com',
    getenv('STORAGE_API_TOKEN'),
);

$list = $credentials->listCredentials('keboola.ex-google-drive');
```

### Manager (application token)

```php
use Keboola\OAuthV2Api\Manager;

$manager = new Manager(
    'https://oauth.keboola.com',
    getenv('APPLICATION_TOKEN'),
);

$components = $manager->listComponents();
```

The application token is optional. When omitted (or `null`), the client authenticates
with the projected Kubernetes ServiceAccount token instead of `X-KBC-ManageApiToken`:

```php
$manager = new Manager('https://oauth.keboola.com');
```

On a failed request both clients throw
`Keboola\OAuthV2Api\Exception\ClientException` (a subclass of
`Keboola\ApiClientBase\Exception\ClientException`), which exposes
`getStatusCode()` and `getResponseBody()` for the failing response when available.

## Development

```bash
docker compose run dev composer install
docker compose run dev composer ci
```

## License

MIT licensed, see [LICENSE](./LICENSE) file.
