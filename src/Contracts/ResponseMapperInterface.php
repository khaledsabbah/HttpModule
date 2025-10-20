<?php
namespace Idaratech\Integrations\Contracts;

use Idaratech\Integrations\Contracts\IResponse as ResponseInterface;

interface ResponseMapperInterface
{
    /** @return mixed */
    public function map(ResponseInterface $response);
}
