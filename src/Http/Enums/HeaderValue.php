<?php
namespace Idaratech\Integrations\Http\Enums;

use ArchTech\Enums\Values;

enum HeaderValue: string
{
    use Values;
    case JSON = 'application/json';
    case FORM_URLENCODED = 'application/x-www-form-urlencoded';
    case MULTIPART = 'multipart/form-data';
    case XML = 'application/xml';
    case TEXT = 'text/plain';
    case GZIP = 'gzip';
    case DEFLATE = 'deflate';
    case BR = 'br';
    case NO_CACHE = 'no-cache';
    case NO_STORE = 'no-store';
    case KEEP_ALIVE = 'keep-alive';
    case CLOSE = 'close';
    public function val(): string { return $this->value; }
}
