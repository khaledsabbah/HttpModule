<?php
namespace Idaratech\Integrations\Dto\Contracts;

use Idaratech\Integrations\Http\Contracts\IResponse as ResponseInterface;

interface ResponseMapperInterface
{
    /** @return mixed */
    public function map(ResponseInterface $response);
}
