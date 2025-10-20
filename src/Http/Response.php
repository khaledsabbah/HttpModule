<?php

namespace Idaratech\Integrations\Http;

use Illuminate\Http\Client\Response as HttpResponse;
use Idaratech\Integrations\Contracts\IRequest;
use Idaratech\Integrations\Contracts\IResponse;

class Response implements IResponse
{
    public function __construct(
        protected IRequest $request,
        protected HttpResponse $httpResponse
    ) {}

    public function request(): IRequest
    {
        return $this->request;
    }

    /**
     * Return decoded JSON, or a value by key (dot-notation supported by Laravel),
     * or $default if the key is missing / not JSON.
     */
    public function json(string $key = null, mixed $default = null): mixed
    {
        try {
            // Illuminate\Http\Client\Response::json($key = null, $default = null)
            return $this->httpResponse->json($key, $default);
        } catch (\Throwable) {
            return $key === null ? null : $default;
        }
    }

    public function statusCode(): int
    {
        return $this->httpResponse->status();
    }

    public function successful(): bool
    {
        return $this->httpResponse->successful();
    }

    public function failed(): bool
    {
        return $this->httpResponse->failed();
    }

    public function body(): string
    {
        return $this->httpResponse->body();
    }

    public function reason(): string
    {
        // Laravel provides the reason phrase (e.g., "OK", "Bad Request")
        return (string) $this->httpResponse->reason();
    }

    /* ---------- Optional helpers (not required by the interface) ---------- */

    /** Access to the raw Illuminate response if needed. */
    public function raw(): HttpResponse
    {
        return $this->httpResponse;
    }
}
