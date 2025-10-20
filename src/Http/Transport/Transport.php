<?php
namespace Idaratech\Integrations\Http\Transport;

use Illuminate\Http\Client\Response as HttpResponse;

interface Transport
{
    /** @param array<string,mixed> $options */
    public function send(string $method, string $url, array $options = []): HttpResponse;
}
