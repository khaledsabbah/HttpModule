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

    public function __construct(string $prefix = 'circuit_breaker', ?string $connection = null)
    {
        $this->redis = Redis::connection($connection);
        $this->prefix = $prefix;
    }

    protected function key(string $service, string $suffix): string
    {
        return "{$this->prefix}:{$service}:{$suffix}";
    }

    public function getState(string $service): CircuitState
    {
        $state = $this->redis->get($this->key($service, 'state'));

        return CircuitState::tryFrom($state ?: '') ?? CircuitState::CLOSED;
    }

    public function setState(string $service, CircuitState $state, ?int $ttl = null): void
    {
        $key = $this->key($service, 'state');

        if ($ttl !== null) {
            $this->redis->setex($key, $ttl, $state->value);
        } else {
            $this->redis->set($key, $state->value);
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
        $count = $this->redis->incr($key);
        $this->redis->expire($key, $ttl);

        return (int) $count;
    }

    public function getFailureCount(string $service): int
    {
        return (int) ($this->redis->get($this->key($service, 'failures')) ?: 0);
    }

    public function getSuccessCount(string $service): int
    {
        return (int) ($this->redis->get($this->key($service, 'successes')) ?: 0);
    }

    public function getRequestCount(string $service): int
    {
        return $this->getFailureCount($service) + $this->getSuccessCount($service);
    }

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

    public function getOpenedAt(string $service): ?int
    {
        $value = $this->redis->get($this->key($service, 'opened_at'));

        return $value ? (int) $value : null;
    }

    public function setOpenedAt(string $service, int $timestamp): void
    {
        $this->redis->set($this->key($service, 'opened_at'), $timestamp);
    }

    public function getHalfOpenSuccessCount(string $service): int
    {
        return (int) ($this->redis->get($this->key($service, 'half_open_successes')) ?: 0);
    }

    public function incrementHalfOpenSuccess(string $service): int
    {
        return (int) $this->redis->incr($this->key($service, 'half_open_successes'));
    }

    public function resetHalfOpenSuccess(string $service): void
    {
        $this->redis->del([$this->key($service, 'half_open_successes')]);
    }
}
