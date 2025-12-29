<?php

namespace Idaratech\Integrations;

use Idaratech\Integrations\CircuitBreaker\CircuitBreakerFactory;
use Idaratech\Integrations\Console\Commands\CircuitBreakerControlCommand;
use Idaratech\Integrations\Console\Commands\CircuitBreakerStatusCommand;
use Idaratech\Integrations\Contracts\ResponseMapperInterface;
use Idaratech\Integrations\Dto\DefaultResponseMapper;
use Idaratech\Integrations\Http\Transport\LaravelHttpTransport;
use Idaratech\Integrations\Http\Transport\Transport;
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

        // Register circuit breaker factory for per-service configuration
        $this->app->singleton(CircuitBreakerFactory::class, function () {
            return new CircuitBreakerFactory();
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/integrations.php' => config_path('integrations.php'),
        ], 'config');
    }
}
