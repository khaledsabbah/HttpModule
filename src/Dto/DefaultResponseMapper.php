<?php
namespace Idaratech\Integrations\Dto;

use Idaratech\Integrations\Dto\Contracts\ResponseMapperInterface;
use Idaratech\Integrations\Http\Contracts\IResponse as ResponseInterface;

class DefaultResponseMapper implements ResponseMapperInterface
{
    public function map(ResponseInterface $response)
    {
        if (class_exists('App\\Dtos\\ResponseDto') && method_exists('App\\Dtos\\ResponseDto', 'fromResponse')) {
            return \App\Dtos\ResponseDto::fromResponse($response);
        }
        return [
            'ok'        => $response->success(),
            'status'    => $response->status(),
            'json'      => $response->json(),
            'headers'   => $response->headers(),
            'raw'       => $response->body(),
        ];
    }
}
