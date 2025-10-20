<?php
namespace Idaratech\Integrations\Http\Support;

use Illuminate\Http\Client\Response as HttpResponse;
use Idaratech\Integrations\Http\Contracts\IResponse as ResponseInterface;
use Idaratech\Integrations\Http\Contracts\IRequest as RequestInterface;
use Idaratech\Integrations\Http\Response;

class ResponseFactory
{
    public function create(RequestInterface $request, HttpResponse $res): ResponseInterface
    {
        return new Response($request, $res);
    }
}
