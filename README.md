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

#### GitHub App installation token

`mintOrganizationToken()` gets a short-lived GitHub App installation token for one
repository of the organization's GitHub connection. The endpoint accepts only an application
identity (application token or ServiceAccount token), never a Storage API token. The returned
`#token` is a `KBC::ProjectSecure::` cipher for the given project and component.

```php
$manager = new Manager(
    'https://oauth.keboola.com',
    connectTimeout: 3,
    requestTimeout: 8,
    backoffMaxTries: 0,
);

$token = $manager->mintOrganizationToken(
    organizationId: '123',
    projectId: '456',
    componentId: 'keboola.dbt-transformation',
    repository: 'https://github.com/keboola/my-dbt-project',
);
// ['#token' => 'KBC::ProjectSecure::...', 'expiresAt' => '2026-01-01T12:00:00+00:00']
```

A failed request throws `ClientException` with the response status code. 400, 404, 412 and 422
mean the request cannot succeed as it is (unknown project or GitHub host, no GitHub connection
for the organization, repository not covered by the installation). 401 and 403 are about the
caller's identity, and 5xx is a server error. A connection failure has no status code.
A successful response that is not valid JSON, has no non-empty `#token` or has no string
`expiresAt` also throws `ClientException`; its status code is either `null` or the 2xx code.

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
