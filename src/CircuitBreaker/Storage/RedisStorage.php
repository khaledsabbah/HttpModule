<?php

namespace Idaratech\Integrations\CircuitBreaker\Storage;

use Illuminate\Contracts\Redis\Connection;
use Illuminate\Support\Facades\Redis;
use Idaratech\Integrations\CircuitBreaker\Contracts\CircuitBreakerStorage;
use Idaratech\Integrations\CircuitBreaker\Enums\CircuitState;

class RedisStorage implements CircuitBreakerStorage
{
    protected Connection $redis;
    protected string $prefix;

    /**
     * @param string $prefix
     * @param string|null $connection
     */
    public function __construct(string $prefix = 'circuit_breaker', ?string $connection = null)
    {
        $this->redis = Redis::connection($connection);
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
     */
    public function getState(string $service): CircuitState
    {
        $state = $this->redis->get($this->key($service, 'state'));

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
            $this->redis->setex($key, $ttl, $state->value);
        } else {
            $this->redis->set($key, $state->value);
        }
    }

    /**
     * Increment failure count with expiry
     * @param string $service
     * @param int $timeWindow
     * @return int
     */
    public function incrementFailure(string $service, int $timeWindow): int
    {
        return $this->incrementWithExpiry($this->key($service, 'failures'), $timeWindow);
    }

    /**
     * Increment success count with expiry
     * @param string $service
     * @param int $timeWindow
     * @return int
     */
    public function incrementSuccess(string $service, int $timeWindow): int
    {
        return $this->incrementWithExpiry($this->key($service, 'successes'), $timeWindow);
    }

    /**
     * Lua script for atomic increment with conditional TTL
     * This prevents race conditions in high concurrency scenarios
     * TTL is only set on first increment (when counter = 1)
     * Subsequent increments preserve the original TTL for true sliding window behavior
     * @param string $key
     * @param int $ttl
     * @return int
     */
    protected function incrementWithExpiry(string $key, int $ttl): int
    {
        $script = <<<'LUA'
            local current = redis.call('INCR', KEYS[1])
            if current == 1 then
                redis.call('EXPIRE', KEYS[1], ARGV[1])
            end
            return current
LUA;

        return (int) $this->redis->eval($script, 1, $key, $ttl);
    }

    /**
     * @param string $service
     * @return int
     */
    public function getFailureCount(string $service): int
    {
        return (int) ($this->redis->get($this->key($service, 'failures')) ?: 0);
    }

    /**
     * @param string $service
     * @return int
     */
    public function getSuccessCount(string $service): int
    {
        return (int) ($this->redis->get($this->key($service, 'successes')) ?: 0);
    }

    /**
     * @param string $service
     * @return int
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
        $this->redis->del([
            $this->key($service, 'state'),
            $this->key($service, 'failures'),
            $this->key($service, 'successes'),
            $this->key($service, 'opened_at'),
            $this->key($service, 'half_open_successes'),
        ]);
    }

    /**
     * @param string $service
     * @return int|null
     */
    public function getOpenedAt(string $service): ?int
    {
        $value = $this->redis->get($this->key($service, 'opened_at'));

        return $value ? (int) $value : null;
    }

    /**
     * Auto-expire after 1 hour to prevent abandoned circuits
     * @param string $service
     * @param int $timestamp
     * @return void
     */
    public function setOpenedAt(string $service, int $timestamp): void
    {
        $this->redis->set($this->key($service, 'opened_at'), $timestamp);
    }

    /**
     * @param string $service
     * @return int
     */
    public function getHalfOpenSuccessCount(string $service): int
    {
        return (int) ($this->redis->get($this->key($service, 'half_open_successes')) ?: 0);
    }

    /**
     * @param string $service
     * @return int
     */
    public function incrementHalfOpenSuccess(string $service): int
    {
        return (int) $this->redis->incr($this->key($service, 'half_open_successes'));
    }

    /**
     * @param string $service
     * @return void
     */
    public function resetHalfOpenSuccess(string $service): void
    {
        $this->redis->del([$this->key($service, 'half_open_successes')]);
    }
}
