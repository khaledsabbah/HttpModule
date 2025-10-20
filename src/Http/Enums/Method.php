<?php
namespace Idaratech\Integrations\Http\Enums;

use ArchTech\Enums\Values;

enum Method: string
{
    use Values;

    case GET     = 'GET';
    case POST    = 'POST';
    case PUT     = 'PUT';
    case PATCH   = 'PATCH';
    case DELETE  = 'DELETE';
    case HEAD    = 'HEAD';
    case OPTIONS = 'OPTIONS';

    public function verb(): string
    {
        return $this->value;
    }
}
