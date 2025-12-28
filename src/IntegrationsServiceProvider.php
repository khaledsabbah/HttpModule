<?php

namespace Idaratech\Integrations;

use Idaratech\Integrations\CircuitBreaker\CircuitBreaker;
use Idaratech\Integrations\CircuitBreaker\CircuitBreakerFactory;
use Idaratech\Integrations\CircuitBreaker\Contracts\CircuitBreakerStorage;
use Idaratech\Integrations\CircuitBreaker\Storage\CacheStorage;
use Idaratech\Integrations\CircuitBreaker\Storage\RedisStorage;
use Idaratech\Integrations\CircuitBreaker\Strategy\CountStrategy;
use Idaratech\Integrations\CircuitBreaker\Strategy\RateStrategy;
use Idaratech\Integrations\Contracts\ResponseMapperInterface;
use Idaratech\Integrations\Dto\DefaultResponseMapper;
use Idaratech\Integrations\Http\Transport\LaravelHttpTransport;
use Idaratech\Integrations\Http\Transport\Transport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class IntegrationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/integrations.php', 'integrations');

        $this->app->bind(Transport::class, function ($app) {
            $headers = config('integrations.default_headers', []);
            $timeout = config('integrations.timeout', null);
            $retry   = config('integrations.retry.times', 0);
            $sleep   = config('integrations.retry.sleep_ms', 0);

            return (new LaravelHttpTransport($headers, $timeout, $retry, $sleep));
        });

        $this->app->bind(ResponseMapperInterface::class, DefaultResponseMapper::class);

        $this->registerCircuitBreaker();
    }

    protected function registerCircuitBreaker(): void
    {
        // Register circuit breaker factory for per-service configuration
        $this->app->singleton(CircuitBreakerFactory::class, function () {
            return new CircuitBreakerFactory();
        });

        // Register global circuit breaker singleton as fallback
        // Used only when circuitBreakerConfig() is not overridden and enabled in config
        $this->app->singleton(CircuitBreaker::class, function ($app) {
            $config = config('integrations.circuit_breaker');

            // Create storage adapter
            $storage = $this->createStorageFromConfig($config);

            // Create strategy
            $strategy = match ($config['strategy']) {
                'count' => new CountStrategy(
                    $config['time_window'],
                    $config['failure_count_threshold'],
                    $config['interval_to_half_open'],
                    $config['success_threshold']
                ),
                default => new RateStrategy(
                    $config['time_window'],
                    $config['failure_rate_threshold'],
                    $config['minimum_requests'],
                    $config['interval_to_half_open'],
                    $config['success_threshold']
                ),
            };

            return (new CircuitBreaker($storage, $strategy))
                ->setFailureStatusCodes($config['failure_status_codes'] ?? [])
                ->setIgnoredStatusCodes($config['ignored_status_codes'] ?? []);
        });
    }

    protected function createStorageFromConfig(array $config): CircuitBreakerStorage
    {
        if ($config['storage'] === 'redis') {
            try {
                return new RedisStorage($config['prefix'], $config['redis_connection'] ?? null);
            } catch (\Throwable $e) {
                Log::warning('Circuit breaker: Redis unavailable, falling back to cache', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
        return new CacheStorage($config['prefix'], $config['cache_store'] ?? null);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/integrations.php' => config_path('integrations.php'),
        ], 'config');
    }
}
