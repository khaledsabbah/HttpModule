<?php

namespace Idaratech\Integrations\CircuitBreaker\Storage;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Idaratech\Integrations\CircuitBreaker\Contracts\CircuitBreakerStorage;
use Idaratech\Integrations\CircuitBreaker\Enums\CircuitState;
use Psr\SimpleCache\InvalidArgumentException;

class CacheStorage implements CircuitBreakerStorage
{
    protected Repository $cache;
    protected string $prefix;

    /**
     * @param string $prefix
     * @param string|null $store
     */
    public function __construct(string $prefix = 'circuit_breaker', ?string $store = null)
    {
        $this->cache = Cache::store($store);
        $this->prefix = $prefix;
    }

    /**
     * @param string $service
     * @param string $suffix
     * @return string
     */
    protected function key(string $service, string $suffix): string
    {
        return "{$this->prefix}:{$service}:{$suffix}";
    }

    /**
     * @param string $service
     * @return CircuitState
     * @throws InvalidArgumentException
     */
    public function getState(string $service): CircuitState
    {
        $state = $this->cache->get($this->key($service, 'state'));

        return CircuitState::tryFrom($state ?: '') ?? CircuitState::CLOSED;
    }

    /**
     * @param string $service
     * @param CircuitState $state
     * @param int|null $ttl
     * @return void
     */
    public function setState(string $service, CircuitState $state, ?int $ttl = null): void
    {
        $key = $this->key($service, 'state');

        if ($ttl !== null) {
            $this->cache->put($key, $state->value, $ttl);
        } else {
            $this->cache->forever($key, $state->value);
        }
    }

    /**
     * @param string $service
     * @param int $timeWindow
     * @return int
     */
    public function incrementFailure(string $service, int $timeWindow): int
    {
        return $this->incrementWithExpiry($this->key($service, 'failures'), $timeWindow);
    }

    /**
     * @param string $service
     * @param int $timeWindow
     * @return int
     */
    public function incrementSuccess(string $service, int $timeWindow): int
    {
        return $this->incrementWithExpiry($this->key($service, 'successes'), $timeWindow);
    }

    /**
     * @param string $key
     * @param int $ttl
     * @return int
     */
    protected function incrementWithExpiry(string $key, int $ttl): int
    {
        $this->cache->add($key, 0, $ttl);

        return (int)$this->cache->increment($key);
    }

    /**
     * @param string $service
     * @return int
     * @throws InvalidArgumentException
     */
    public function getFailureCount(string $service): int
    {
        return (int)$this->cache->get($this->key($service, 'failures'), 0);
    }

    /**
     * @param string $service
     * @return int
     * @throws InvalidArgumentException
     */
    public function getSuccessCount(string $service): int
    {
        return (int)$this->cache->get($this->key($service, 'successes'), 0);
    }

    /**
     * @param string $service
     * @return int
     * @throws InvalidArgumentException
     */
    public function getRequestCount(string $service): int
    {
        return $this->getFailureCount($service) + $this->getSuccessCount($service);
    }

    /**
     * @param string $service
     * @return void
     */
    public function reset(string $service): void
    {
        $suffixes = ['state', 'failures', 'successes', 'opened_at', 'half_open_successes'];

        foreach ($suffixes as $suffix) {
            $this->cache->forget($this->key($service, $suffix));
        }
    }

    /**
     * @param string $service
     * @return int|null
     * @throws InvalidArgumentException
     */
    public function getOpenedAt(string $service): ?int
    {
        $value = $this->cache->get($this->key($service, 'opened_at'));

        return $value ? (int)$value : null;
    }

    /**
     * Auto-expire after 1 hour to prevent abandoned circuits
     * @param string $service
     * @param int $timestamp
     * @return void
     */
    public function setOpenedAt(string $service, int $timestamp): void
    {
        $this->cache->forever($this->key($service, 'opened_at'), $timestamp);
    }

    /**
     * @param string $service
     * @return int
     * @throws InvalidArgumentException
     */
    public function getHalfOpenSuccessCount(string $service): int
    {
        return (int)$this->cache->get($this->key($service, 'half_open_successes'), 0);
    }

    /**
     * @param string $service
     * @return int
     */
    public function incrementHalfOpenSuccess(string $service): int
    {
        return (int) $this->cache->increment($this->key($service, 'half_open_successes'));
    }

    /**
     * @param string $service
     * @return void
     */
    public function resetHalfOpenSuccess(string $service): void
    {
        $this->cache->forget($this->key($service, 'half_open_successes'));
    }
}
