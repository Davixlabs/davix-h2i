# davix/h2i-sdk (PHP)

Official Davix H2I client for PHP 7.4+ (ext-curl, ext-json). Methods, enum constants and metadata are **generated from the canonical Davix H2I contract**.

## Install (private)

Every app release pushes this SDK, with the same version number, to the private repository `Davixlabs/davix-h2i-sdk-php`. Composer installs it from there:

```json
{
  "repositories": [{ "type": "vcs", "url": "https://github.com/Davixlabs/davix-h2i-sdk-php" }],
  "require": { "davix/h2i-sdk": "^1.3" }
}
```

Give Composer read access once with `composer config --global github-oauth.github.com YOUR_GITHUB_TOKEN`. Without Composer, download
`davix-h2i-php-sdk-<version>.tgz` from the app release page and `require 'autoload.php';`.

## Use

```php
require 'vendor/autoload.php';   // or require 'autoload.php';
$h2i = new \Davix\H2I\Client(['apiKey' => getenv('DAVIX_H2I_API_KEY'), 'baseUrl' => 'https://your-davix-h2i-host']);

$res = $h2i->images()->resize(['images' => '/path/photo.jpg', 'width' => 800, 'resizeMode' => \Davix\H2I\Enums\ResizeFit::COVER]);
echo $res->requestId;
```

* **Auth**: `X-Api-Key` header only. No secrets beyond the API key exist in the SDK.
* **Metadata**: `$h2i->schema('images.resize')` (min/max/enum/default/type/required/behavior), `$h2i->capabilities()` (runtime), `$h2i->staticCapabilities()`, `$h2i->accountCapabilities()`, `$h2i->refreshContract()` for the live contract; the bundled one stays available.
* **Enums**: final classes with constants (`\Davix\H2I\Enums\ImageFormat::WEBP`), usable on PHP 7.4 and later.
* **Errors**: `\Davix\H2I\Exceptions\DavixH2IException` subclasses expose the public properties `status`, `errorCode`, `requestId`, `hint`, `details`, `renewalUrl`, `retryable`, `retryAfterMs` and `body`.
* **Retries**: only retryable errors, only for safe methods or calls with an `idempotencyKey` option; keys are never auto-invented for the caller.
* **Uploads**: file paths, raw strings wrapped as `['data' => …, 'filename' => …]`, or streams. A plain string is read from disk only for contract file fields.
* **Escape hatch**: `$h2i->request('GET', '/v1/usage')` refuses `/internal`, admin and non-contract paths.

* **Live slices**: `$h2i->contractEndpoint($id)`, `contractField($id, $name)`, `contractEnum($name)`, `contractError($code)`, `contractCategories()` and `$h2i->capabilities()->section('pdfs')` / `->feature('pdfs', 'ocr')` fetch just the piece you need.

Tests: `php tests/run.php` (needs `node` for the shared mock server).

## Naming your app in the History
Every processing call takes an optional `app`, shown under each result in your History: `$h2i->images()->resize(['images' => '/path/photo.jpg', 'width' => 800, 'app' => 'My shop importer'])`.
