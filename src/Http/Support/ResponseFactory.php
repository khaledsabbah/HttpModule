<?php
namespace Idaratech\Integrations\Http\Support;

use Idaratech\Integrations\Http\Response;
use Illuminate\Http\Client\Response as HttpResponse;
use Idaratech\Integrations\Contracts\IResponse as ResponseInterface;
use Idaratech\Integrations\Contracts\IRequest as RequestInterface;


class ResponseFactory
{
    public function create(RequestInterface $request, HttpResponse $res): ResponseInterface
    {
        return new Response($request, $res);
    }
}
