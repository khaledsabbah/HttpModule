<?php

namespace Idaratech\Integrations\CircuitBreaker\Config;

/**
 * Abstract base class for circuit breaker configuration.
 *
 * Provides shared configuration properties and methods that are common
 * to all circuit breaker strategies.
 */
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
     * Set the time window in seconds to track requests/failures.
     *
     * @param int $seconds Time window in seconds
     * @return static
     */
    public function timeWindow(int $seconds): static
    {
        $this->timeWindow = $seconds;
        return $this;
    }

    /**
     * Set the interval in seconds before attempting recovery (OPEN → HALF_OPEN).
     *
     * @param int $seconds Interval in seconds
     * @return static
     */
    public function intervalToHalfOpen(int $seconds): static
    {
        $this->intervalToHalfOpen = $seconds;
        return $this;
    }

    /**
     * Set the number of consecutive successes needed to close the circuit.
     *
     * @param int $count Number of successes
     * @return static
     */
    public function successThreshold(int $count): static
    {
        $this->successThreshold = $count;
        return $this;
    }

    /**
     * Set the storage adapter type ('redis' or 'cache').
     *
     * @param string $type Storage type
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
     * @param string $prefix Cache key prefix
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
     * @param string|null $connection Redis connection name
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
     * @param string|null $store Cache store name
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
     * @param array $codes Array of HTTP status codes
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
     * @param array $codes Array of HTTP status codes
     * @return static
     */
    public function ignoredStatusCodes(array $codes): static
    {
        $this->ignoredStatusCodes = $codes;
        return $this;
    }

    // Getters

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

    public function getPrefix(): ?string
    {
        return $this->prefix;
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
