<?php
namespace Idaratech\Integrations\Http\Support;

use Idaratech\Integrations\Logger;
use Idaratech\Integrations\Contracts\IResponse as ResponseInterface;

class HttpLogger
{
    public function logRequest(array $payload): void { Logger::info('request', $payload); }

    public function logResponse(ResponseInterface $response): void
    {
        $payload = ['status'=>$response->statusCode(),'headers'=>$response->request()->headers(),'json'=>$response->json()];
        if ($response->statusCode() >= 500) Logger::error('response', $payload);
        elseif ($response->statusCode() >= 400) Logger::warning('response', $payload);
        else Logger::info('response', $payload);
    }

    public function logException(\Throwable $e): void
    {
        Logger::error('CLIENT_ERROR', ['type'=>get_class($e),'message'=>$e->getMessage()]);
    }
}
