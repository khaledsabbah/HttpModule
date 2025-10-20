<?php
namespace Idaratech\Integrations\Http\Support;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Idaratech\Integrations\Http\Contracts\IRequest as RequestInterface;

class RequestContextBuilder
{
    public function __construct(
        protected ?string $baseUri = null,
        protected array $clientHeaders = [],
        protected array $clientOptions = [],
    ) {}

    public function withBaseUri(?string $baseUri): self { $this->baseUri = $baseUri; return $this; }
    public function withHeaders(array $headers): self { $this->clientHeaders = $headers; return $this; }
    public function withOptions(array $options): self { $this->clientOptions = $options; return $this; }

    /** @return array{url:string, options:array<string,mixed>} */
    public function build(RequestInterface $request): array
    {
        $url = $this->resolveUrl($request->fullUri());
        $options = $this->mergeOptions($request);
        return ['url' => $url, 'options' => $options];
    }

    protected function resolveUrl(string $uri): string
    {
        if ($this->baseUri && ! str_starts_with($uri, 'http://') && ! str_starts_with($uri, 'https://')) {
            return rtrim($this->baseUri, '/') . '/' . ltrim($uri, '/');
        }
        return $uri;
    }

    /** @return array<string,mixed> */
    protected function mergeOptions(RequestInterface $request): array
    {
        $opts = $this->clientOptions;
        $opts = array_replace($opts, $request->options());

        if ($q = $request->query()) {
            $opts[RequestOptions::QUERY] = isset($opts[RequestOptions::QUERY])
                ? array_merge((array) $opts[RequestOptions::QUERY], $q)
                : $q;
        }

        $body = $request->body();
        if (!empty($body)) {
            $contentType = $this->detectContentType(array_merge($this->clientHeaders, $request->headers()));
            if ($contentType === 'multipart/form-data') {
                $opts[RequestOptions::MULTIPART] = $this->toMultipart($body);
            } elseif ($contentType === 'application/x-www-form-urlencoded') {
                $opts[RequestOptions::FORM_PARAMS] = $body;
            } else {
                $opts[RequestOptions::JSON] = $body;
            }
        }

        return $opts;
    }

    protected function detectContentType(array $headers): ?string
    {
        $headers = array_change_key_case($headers, CASE_LOWER);
        return isset($headers['content-type']) ? (string) $headers['content-type'] : null;
    }

    protected function toMultipart(array $data): array
    {
        $out = [];
        foreach ($data as $name => $value) {
            if (is_array($value) && Arr::isAssoc($value) && array_key_exists('contents', $value)) {
                $part = array_merge(['name' => $name], $value);
            } else {
                $part = ['name' => $name, 'contents' => is_scalar($value) ? (string) $value : json_encode($value)];
            }
            $out[] = $part;
        }
        return $out;
    }
}
