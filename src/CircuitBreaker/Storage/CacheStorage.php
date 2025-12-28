<?php

namespace Idaratech\Integrations\CircuitBreaker\Storage;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Idaratech\Integrations\CircuitBreaker\Contracts\CircuitBreakerStorage;
use Idaratech\Integrations\CircuitBreaker\Enums\CircuitState;
use Psr\SimpleCache\InvalidArgumentException;

class CacheStorage implements CircuitBreakerStorage
{
    protected Repository $cache;
    protected string $prefix;

    public function __construct(string $prefix = 'circuit_breaker', ?string $store = null)
    {
        $this->cache = Cache::store($store);
        $this->prefix = $prefix;
    }

    protected function key(string $service, string $suffix): string
    {
        return "{$this->prefix}:{$service}:{$suffix}";
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getState(string $service): CircuitState
    {
        $state = $this->cache->get($this->key($service, 'state'));

        return CircuitState::tryFrom($state ?: '') ?? CircuitState::CLOSED;
    }

    public function setState(string $service, CircuitState $state, ?int $ttl = null): void
    {
        $key = $this->key($service, 'state');

        if ($ttl !== null) {
            $this->cache->put($key, $state->value, $ttl);
        } else {
            $this->cache->forever($key, $state->value);
        }
    }

    public function incrementFailure(string $service, int $timeWindow): int
    {
        return $this->incrementWithExpiry($this->key($service, 'failures'), $timeWindow);
    }

    public function incrementSuccess(string $service, int $timeWindow): int
    {
        return $this->incrementWithExpiry($this->key($service, 'successes'), $timeWindow);
    }

    protected function incrementWithExpiry(string $key, int $ttl): int
    {
        // Add key with TTL if it doesn't exist, then increment
        $this->cache->add($key, 0, $ttl);

        return (int)$this->cache->increment($key);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getFailureCount(string $service): int
    {
        return (int)$this->cache->get($this->key($service, 'failures'), 0);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getSuccessCount(string $service): int
    {
        return (int)$this->cache->get($this->key($service, 'successes'), 0);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getRequestCount(string $service): int
    {
        return $this->getFailureCount($service) + $this->getSuccessCount($service);
    }

    public function reset(string $service): void
    {
        $suffixes = ['state', 'failures', 'successes', 'opened_at', 'half_open_successes'];

        foreach ($suffixes as $suffix) {
            $this->cache->forget($this->key($service, $suffix));
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getOpenedAt(string $service): ?int
    {
        $value = $this->cache->get($this->key($service, 'opened_at'));

        return $value ? (int)$value : null;
    }

    public function setOpenedAt(string $service, int $timestamp): void
    {
        $this->cache->forever($this->key($service, 'opened_at'), $timestamp);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getHalfOpenSuccessCount(string $service): int
    {
        return (int)$this->cache->get($this->key($service, 'half_open_successes'), 0);
    }

    public function incrementHalfOpenSuccess(string $service): int
    {
        $key = $this->key($service, 'half_open_successes');

        $this->cache->add($key, 0, Carbon::now()->addDay());

        return (int)$this->cache->increment($key);
    }

    public function resetHalfOpenSuccess(string $service): void
    {
        $this->cache->forget($this->key($service, 'half_open_successes'));
    }
}
