<?php
namespace Idaratech\Integrations\Http\Enums;

use ArchTech\Enums\Values;

enum HeaderKey: string
{
    use Values;
    case ACCEPT = 'Accept';
    case AUTHORIZATION = 'Authorization';
    case CONTENT_TYPE = 'Content-Type';
    case USER_AGENT = 'User-Agent';
    case CACHE_CONTROL = 'Cache-Control';
    case CONTENT_ENCODING = 'Content-Encoding';
    case CONNECTION = 'Connection';
    case CONTENT_LENGTH = 'Content-Length';
    case HOST = 'Host';
    case ORIGIN = 'Origin';
    case REFERER = 'Referer';
    case TENANT_ID = 'X-Tenant-Id';
    case REQUEST_ID = 'X-Request-Id';
    case API_KEY = 'X-Api-Key';
    public function key(): string { return $this->value; }
}
