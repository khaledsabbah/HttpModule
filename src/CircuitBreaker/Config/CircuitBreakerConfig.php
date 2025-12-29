<?php

namespace Idaratech\Integrations\CircuitBreaker\Config;

abstract class CircuitBreakerConfig
{
    protected int $timeWindow = 60;
    protected int $intervalToHalfOpen = 30;
    protected int $successThreshold = 3;
    protected string $storage = 'cache';
    protected ?string $prefix = null;
    protected ?string $redisConnection = null;
    protected ?string $cacheStore = null;
    protected array $failureStatusCodes = [500, 502, 503, 504];
    protected array $ignoredStatusCodes = [];

    /**
     * @param int $seconds
     * @return static
     */
    public function timeWindow(int $seconds): static
    {
        if ($seconds < 1) {
            throw new \InvalidArgumentException(
                "Time window must be at least 1 seconds, got: {$seconds}"
            );
        }
        $this->timeWindow = $seconds;
        return $this;
    }

    /**
     * Set the interval in seconds before attempting recovery (OPEN → HALF_OPEN).
     *
     * @param int $seconds
     * @return static
     */
    public function intervalToHalfOpen(int $seconds): static
    {
        if ($seconds < 1) {
            throw new \InvalidArgumentException(
                "Interval to half-open must be at least 1 second, got: {$seconds}"
            );
        }
        $this->intervalToHalfOpen = $seconds;
        return $this;
    }

    /**
     * Set the number of consecutive successes needed to close the circuit.
     *
     * @param int $count
     * @return static
     */
    public function successThreshold(int $count): static
    {
        if ($count < 1) {
            throw new \InvalidArgumentException(
                "Success threshold must be at least 1, got: {$count}"
            );
        }
        $this->successThreshold = $count;
        return $this;
    }

    /**
     * Set the storage adapter type ('redis' or 'cache').
     *
     * @param string $type
     * @return static
     */
    public function storage(string $type): static
    {
        $this->storage = $type;
        return $this;
    }

    /**
     * Set the cache key prefix for circuit breaker state.
     *
     * @param string $prefix
     * @return static
     */
    public function prefix(string $prefix): static
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Set the Redis connection name (only for Redis storage).
     *
     * @param string|null $connection
     * @return static
     */
    public function redisConnection(?string $connection): static
    {
        $this->redisConnection = $connection;
        return $this;
    }

    /**
     * Set the cache store name (only for cache storage).
     *
     * @param string|null $store
     * @return static
     */
    public function cacheStore(?string $store): static
    {
        $this->cacheStore = $store;
        return $this;
    }

    /**
     * Set the HTTP status codes that are considered failures.
     *
     * @param array $codes
     * @return static
     */
    public function failureStatusCodes(array $codes): static
    {
        $this->failureStatusCodes = $codes;
        return $this;
    }

    /**
     * Set the HTTP status codes that should be ignored by the circuit breaker.
     *
     * @param array $codes
     * @return static
     */
    public function ignoredStatusCodes(array $codes): static
    {
        $this->ignoredStatusCodes = $codes;
        return $this;
    }

    public function getTimeWindow(): int
    {
        return $this->timeWindow;
    }

    public function getIntervalToHalfOpen(): int
    {
        return $this->intervalToHalfOpen;
    }

    public function getSuccessThreshold(): int
    {
        return $this->successThreshold;
    }

    public function getStorage(): string
    {
        return $this->storage;
    }

    public function getPrefix(): string
    {
        return $this->prefix ?? config('integrations.circuit_breaker_prefix', 'cb:app');
    }

    public function getRedisConnection(): ?string
    {
        return $this->redisConnection;
    }

    public function getCacheStore(): ?string
    {
        return $this->cacheStore;
    }

    public function getFailureStatusCodes(): array
    {
        return $this->failureStatusCodes;
    }

    public function getIgnoredStatusCodes(): array
    {
        return $this->ignoredStatusCodes;
    }

    /**
     * Get the strategy type ('rate' or 'count').
     *
     * @return string
     */
    abstract public function getStrategy(): string;
}
