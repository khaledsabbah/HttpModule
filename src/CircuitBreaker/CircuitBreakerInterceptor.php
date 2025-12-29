<?php

namespace Idaratech\Integrations\CircuitBreaker;

use Idaratech\Integrations\CircuitBreaker\Exceptions\CircuitOpenException;
use Idaratech\Integrations\Contracts\IRequest;
use Idaratech\Integrations\Contracts\IResponse;
use Psr\SimpleCache\InvalidArgumentException;

class CircuitBreakerInterceptor
{
    protected CircuitBreaker $circuitBreaker;

    public function __construct(CircuitBreaker $circuitBreaker)
    {
        $this->circuitBreaker = $circuitBreaker;
    }


    /**
     * Check if the circuit allows the request (before middleware).
     *
     * @throws CircuitOpenException
     * @throws InvalidArgumentException
     */
    public function before(IRequest $request): void
    {
        $service = $this->resolveServiceName($request);

        if (!$this->circuitBreaker->isAvailable($service)) {
            throw new CircuitOpenException(
                $service,
                $this->circuitBreaker->getState($service)
            );
        }
    }

    /**
     * Record the result after the request completes (after middleware).
     * @throws InvalidArgumentException
     */
    public function after(IRequest $request, IResponse $response): void
    {
        $service = $this->resolveServiceName($request);
        $statusCode = $response->statusCode();

        $this->circuitBreaker->recordHttpResult($service, $statusCode);
    }

    /**
     * Record a failure when an exception occurs.
     * @param IRequest $request
     * @throws InvalidArgumentException
     */
    public function onException(IRequest $request): void
    {
        $service = $this->resolveServiceName($request);
        $this->circuitBreaker->failure($service);
    }

    /**
     * Resolve the service name from the request.
     * @param IRequest $request
     * @return string
     */
    protected function resolveServiceName(IRequest $request): string
    {
        $uri = $request->fullUri();

        if (empty($uri)) {
            $uri = $request->baseUrl() . $request->uri();
        }

        $parsed = parse_url($uri);

        if (isset($parsed['host'])) {
            return $parsed['host'];
        }

        // Fallback to base URL or URI
        return $request->baseUrl() ?: $request->uri();
    }

}
