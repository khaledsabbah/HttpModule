<?php
namespace Idaratech\Integrations\Http\Transport;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Http;
use Idaratech\Integrations\Logger;

class LaravelHttpTransport implements Transport
{
    protected array $headers = [];
    protected ?int $timeout = null;
    protected int $retryTimes = 0;
    protected int $retrySleepMs = 0;

    public function __construct(array $headers = [], ?int $timeout = null, int $retryTimes = 0, int $retrySleepMs = 0)
    {
        $this->headers = $headers;
        $this->timeout = $timeout;
        $this->retryTimes = $retryTimes;
        $this->retrySleepMs = $retrySleepMs;
    }

    public function withHeaders(array $headers): self { $this->headers = array_merge($this->headers, $headers); return $this; }
    public function timeout(?int $seconds): self { $this->timeout = $seconds; return $this; }
    public function retry(int $times, int $sleepMs = 0): self { $this->retryTimes = $times; $this->retrySleepMs = $sleepMs; return $this; }

    /** @param array<string,mixed> $options */
    public function send(string $method, string $url, array $options = []): HttpResponse
    {
        $http = Http::withHeaders($this->headers);
        if ($this->timeout !== null) $http = $http->timeout($this->timeout);
        if ($this->retryTimes > 0) $http = $http->retry($this->retryTimes, $this->retrySleepMs);
        try { return $http->send($method, $url, $options); }
        catch (ConnectionException $e) {
            Logger::error('CLIENT_CONNECTION_EXCEPTION', ['url'=>$url,'method'=>$method,'message'=>$e->getMessage()]);
            return new HttpResponse(new \GuzzleHttp\Psr7\Response(599,['Content-Type'=>'application/json'],json_encode(['error'=>'client_exception','message'=>$e->getMessage()])));
        } catch (\Throwable $e) {
            Logger::error('CLIENT_UNEXPECTED_EXCEPTION', ['url'=>$url,'method'=>$method,'message'=>$e->getMessage()]);
            return new HttpResponse(new \GuzzleHttp\Psr7\Response(599,['Content-Type'=>'application/json'],json_encode(['error'=>'client_exception','message'=>$e->getMessage()])));
        }
    }
}
