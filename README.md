

### Laravel integration
Publish config with:

```
php artisan vendor:publish --provider="Idaratech\Integrations\IntegrationsServiceProvider" --tag=config
```

### Method & Content-Type Enums

Use `MethodEnum` for HTTP verbs and `ContentTypeEnum` for clean content-type headers:

```php
use Idaratech\Integrations\Http\Enums\Method as M;
use Idaratech\Integrations\Http\Enums\ContentType as CT;
use Idaratech\Integrations\Http\Enums\HeaderKey as HK;

$request->headers()[HK::CONTENT_TYPE->key()] = CT::JSON->mime();

public function method(): M
{
    return M::POST;
}
```
