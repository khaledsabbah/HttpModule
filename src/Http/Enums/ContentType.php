<?php
namespace Idaratech\Integrations\Http\Enums;

use ArchTech\Enums\Values;

enum ContentType: string
{
    use Values;

    case JSON                = 'application/json';
    case FORM_URLENCODED     = 'application/x-www-form-urlencoded';
    case MULTIPART_FORM_DATA = 'multipart/form-data';
    case XML                 = 'application/xml';
    case TEXT_PLAIN          = 'text/plain';

    public function mime(): string
    {
        return $this->value;
    }
}
