<?php
namespace Idaratech\Integrations;

use GuzzleHttp\RequestOptions;
use Idaratech\Integrations\Contracts\ResponseMapperInterface;
use Idaratech\Integrations\Dto\DefaultResponseMapper;
use Idaratech\Integrations\Contracts\IClient as ClientInterface;
use Idaratech\Integrations\Contracts\IRequest as RequestInterface;
use Idaratech\Integrations\Contracts\IResponse as ResponseInterface;
use Idaratech\Integrations\Http\Enums\HeaderKey as HK;
use Idaratech\Integrations\Http\Support\HeaderBag;
use Idaratech\Integrations\Http\Support\HttpLogger;
use Idaratech\Integrations\Http\Support\RequestContextBuilder;
use Idaratech\Integrations\Http\Support\ResponseFactory;
use Idaratech\Integrations\Http\Transport\LaravelHttpTransport;
use Idaratech\Integrations\Http\Transport\Transport;
use Illuminate\Http\Client\Response as HttpResponse;

class Client implements ClientInterface
{
    protected array $headers = [];
    protected array $options = [];
    protected ?string $baseUri = null;
    protected ?int $timeout = null;
    protected int $retryTimes = 0;
    protected int $retrySleepMs = 0;

    protected Transport $transport;
    protected RequestContextBuilder $builder;
    protected HttpLogger $logger;
    protected ResponseFactory $responseFactory;
    protected ResponseMapperInterface $mapper;

    public function __construct(?string $baseUri = null, array $headers = [], array $options = [])
    {
        $this->baseUri = $baseUri;
        $this->headers = $headers;
        $this->options = $options;

        $this->transport = new LaravelHttpTransport($this->headers, $this->timeout, $this->retryTimes, $this->retrySleepMs);
        $this->builder = new RequestContextBuilder($this->baseUri, $this->headers, $this->options);
        $this->logger = new HttpLogger();
        $this->responseFactory = new ResponseFactory();
        $this->mapper = new DefaultResponseMapper();
    }

    public function withHeaders(array $headers): ClientInterface
    {
        $normalized = [];
        foreach ($headers as $k => $v) {
            if ($k instanceof HK) { $normalized[$k->key()] = $v; }
            else { $normalized[is_string($k) ? $k : (string) $k] = $v; }
        }
        $this->headers = HeaderBag::merge($this->headers, $normalized);
        $this->transport->withHeaders($this->headers);
        $this->builder->withHeaders($this->headers);
        return $this;
    }

    public function setHeader(string|HK $key, mixed $value): ClientInterface
    {
        if ($key instanceof HK) { $key = $key->key(); }
        $this->headers = HeaderBag::merge($this->headers, [$key => $value]);
        $this->transport->withHeaders($this->headers);
        $this->builder->withHeaders($this->headers);
        return $this;
    }

    public function withBearer(string $token): ClientInterface
    {
        return $this->setHeader(HK::AUTHORIZATION, 'Bearer ' . $token);
    }

    public function withBasicAuth(string $username, string $password): ClientInterface
    {
        $this->options[RequestOptions::AUTH] = [$username, $password];
        $this->builder->withOptions($this->options);
        return $this;
    }

    public function withBaseUri(?string $baseUri): ClientInterface
    {
        $this->baseUri = $baseUri;
        $this->builder->withBaseUri($this->baseUri);
        return $this;
    }

    public function retry(int $times, int $sleepMs = 0): ClientInterface
    {
        $this->retryTimes = $times;
        $this->retrySleepMs = $sleepMs;
        $this->transport->retry($times, $sleepMs);
        return $this;
    }

    public function timeout(int $seconds): ClientInterface
    {
        $this->timeout = $seconds;
        $this->transport->timeout($seconds);
        return $this;
    }

    public function withOption(string $key, mixed $value): ClientInterface
    {
        $this->options[$key] = $value;
        $this->builder->withOptions($this->options);
        return $this;
    }

    public function withOptions(array $options): ClientInterface
    {
        $this->options = array_replace($this->options, $options);
        $this->builder->withOptions($this->options);
        return $this;
    }

    public function do(RequestInterface $request): ResponseInterface
    {
        $request->runBeforeMiddlewares($this);

        $ctx = $this->builder->build($request);
        $url = $ctx['url'];
        $options = $ctx['options'];
        $method = $request->method();
        $mergedHeaders = HeaderBag::merge($this->headers, $request->headers());

        $this->logger->logRequest([
            'method'  => $method,
            'url'     => $url,
            'headers' => $this->redactHeaders($mergedHeaders),
            'query'   => $request->query(),
            'body'    => $this->redactBody($request->body()),
        ]);

        $res = $this->transport
            ->withHeaders($mergedHeaders)
            ->send($method, $url, $options);

        $response = $this->createResponse($request, $res);

        $request->runAfterMiddlewares($this);

        $this->logger->logResponse($response);

        return $response;
    }

    public function process(RequestInterface $request): Contracts\IDto
    {
        $response = $this->do($request);
        return $this->responseToDto($response);
    }

    protected function createResponse(RequestInterface $request, HttpResponse $res): ResponseInterface
    {
        return $this->responseFactory->create($request, $res);
    }

    protected function responseToDto(ResponseInterface $response)
    {
        return $this->mapper->map($response);
    }

    protected function resolveMethod($method): string
    {
        if (is_string($method)) return strtoupper($method);
        if (is_object($method)) {
            if (method_exists($method, 'value')) return strtoupper((string) $method->value);
            if (method_exists($method, 'name')) return strtoupper((string) $method->name);
            if (is_callable($method)) return strtoupper((string) $method());
            if (method_exists($method, '__toString')) return strtoupper((string) $method);
        }
        return 'GET';
    }

    protected function redactHeaders(array $headers): array
    {
        $out = [];
        foreach ($headers as $k => $v) {
            $key = is_string($k) ? strtolower($k) : (string) $k;
            $out[$k] = in_array($key, ['authorization','x-api-key']) ? $this->maskToken((string)$v) : $v;
        }
        return $out;
    }

    protected function redactBody(array $body): array
    {
        $sensitive = ['password','token','access_token','secret','client_secret','authorization'];
        foreach ($sensitive as $k) if (array_key_exists($k, $body)) $body[$k] = '******';
        return $body;
    }

    protected function maskToken(string $value): string
    {
        $len = strlen($value);
        if ($len <= 8) return '******';
        return substr($value, 0, 4) . str_repeat('*', max(0, $len - 8)) . substr($value, -4);
    }
}
