<?php
namespace Idaratech\Integrations\Http\Support;

use Idaratech\Integrations\Logger;
use Idaratech\Integrations\Http\Contracts\IResponse as ResponseInterface;

class HttpLogger
{
    public function logRequest(array $payload): void { Logger::info('request', $payload); }

    public function logResponse(ResponseInterface $response): void
    {
        $payload = ['status'=>$response->status(),'headers'=>$response->headers(),'json'=>$response->json()];
        if ($response->status() >= 500) Logger::error('response', $payload);
        elseif ($response->status() >= 400) Logger::warning('response', $payload);
        else Logger::info('response', $payload);
    }

    public function logException(\Throwable $e): void
    {
        Logger::error('CLIENT_ERROR', ['type'=>get_class($e),'message'=>$e->getMessage()]);
    }
}
