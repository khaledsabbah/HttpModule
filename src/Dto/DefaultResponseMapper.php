<?php
namespace Idaratech\Integrations\Dto;

use Idaratech\Integrations\Contracts\ResponseMapperInterface;
use Idaratech\Integrations\Contracts\IResponse as ResponseInterface;

class DefaultResponseMapper implements ResponseMapperInterface
{
    public function map(ResponseInterface $response)
    {
        return [
            'ok'        => $response->successful(),
            'status'    => $response->statusCode(),
            'data'      => $response->json(),
        ];
    }
}
