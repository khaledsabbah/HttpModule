<?php

namespace Idaratech\Integrations\CircuitBreaker;

use Idaratech\Integrations\CircuitBreaker\Config\CircuitBreakerConfig;
use Idaratech\Integrations\CircuitBreaker\Config\CountStrategyConfig;
use Idaratech\Integrations\CircuitBreaker\Config\RateStrategyConfig;
use Idaratech\Integrations\CircuitBreaker\Contracts\CircuitBreakerStorage;
use Idaratech\Integrations\CircuitBreaker\Contracts\StrategyInterface;
use Idaratech\Integrations\CircuitBreaker\Storage\CacheStorage;
use Idaratech\Integrations\CircuitBreaker\Storage\RedisStorage;
use Idaratech\Integrations\CircuitBreaker\Strategy\CountStrategy;
use Idaratech\Integrations\CircuitBreaker\Strategy\RateStrategy;

/**
 * Factory for creating circuit breaker instances from configuration objects.
 */
class CircuitBreakerFactory
{
    /**
     * Create a circuit breaker from a configuration object.
     *
     * @param CircuitBreakerConfig $config Configuration object
     * @return CircuitBreaker
     */
    public function createFromConfig(CircuitBreakerConfig $config): CircuitBreaker
    {
        $storage = $this->createStorageFromConfig($config);
        $strategy = $this->createStrategyFromConfig($config);

        $circuitBreaker = new CircuitBreaker($storage, $strategy);

        // Set optional configurations
        $circuitBreaker->setFailureStatusCodes($config->getFailureStatusCodes());
        $circuitBreaker->setIgnoredStatusCodes($config->getIgnoredStatusCodes());

        return $circuitBreaker;
    }

    /**
     * Create storage adapter from configuration.
     *
     * @param CircuitBreakerConfig $config
     * @return CircuitBreakerStorage
     */
    protected function createStorageFromConfig(CircuitBreakerConfig $config): CircuitBreakerStorage
    {
        $storageType = $config->getStorage();
        $prefix = $config->getPrefix() ?? config('integrations.circuit_breaker.prefix', 'circuit_breaker');

        if ($storageType === 'redis') {
            return new RedisStorage($prefix, $config->getRedisConnection());
        }

        return new CacheStorage($prefix, $config->getCacheStore());
    }

    /**
     * Create strategy from configuration.
     *
     * @param CircuitBreakerConfig $config
     * @return StrategyInterface
     * @throws \InvalidArgumentException
     */
    protected function createStrategyFromConfig(CircuitBreakerConfig $config): StrategyInterface
    {
        if ($config instanceof CountStrategyConfig) {
            return new CountStrategy(
                timeWindow: $config->getTimeWindow(),
                failureCountThreshold: $config->getFailureCountThreshold(),
                intervalToHalfOpen: $config->getIntervalToHalfOpen(),
                successThreshold: $config->getSuccessThreshold()
            );
        }

        if ($config instanceof RateStrategyConfig) {
            return new RateStrategy(
                timeWindow: $config->getTimeWindow(),
                failureRateThreshold: $config->getFailureRateThreshold(),
                minimumRequests: $config->getMinimumRequests(),
                intervalToHalfOpen: $config->getIntervalToHalfOpen(),
                successThreshold: $config->getSuccessThreshold()
            );
        }

        throw new \InvalidArgumentException('Unknown circuit breaker config type: ' . get_class($config));
    }
}
