<?php

namespace Idaratech\Integrations\CircuitBreaker\Strategy;

use Idaratech\Integrations\CircuitBreaker\Contracts\CircuitBreakerStorage;
use Idaratech\Integrations\CircuitBreaker\Contracts\StrategyInterface;

/**
 * Rate-based circuit breaker strategy.
 *
 * Trips the circuit when the failure rate exceeds a threshold percentage
 * within a specified time window.
 */
class RateStrategy implements StrategyInterface
{
    public function __construct(
        protected readonly int $timeWindow = 60,
        protected readonly float $failureRateThreshold = 50.0,
        protected readonly int $minimumRequests = 10,
        protected readonly int $intervalToHalfOpen = 30,
        protected readonly int $successThreshold = 3
    ) {}

    public function shouldTrip(string $service, CircuitBreakerStorage $storage): bool
    {
        $totalRequests = $storage->getRequestCount($service);

        if ($totalRequests < $this->minimumRequests) {
            return false;
        }

        $failureRate = ($storage->getFailureCount($service) / $totalRequests) * 100;

        return $failureRate >= $this->failureRateThreshold;
    }

    public function shouldClose(string $service, CircuitBreakerStorage $storage): bool
    {
        return $storage->getHalfOpenSuccessCount($service) >= $this->successThreshold;
    }

    public function shouldAttemptReset(string $service, CircuitBreakerStorage $storage): bool
    {
        $openedAt = $storage->getOpenedAt($service);

        return $openedAt === null || (time() - $openedAt) >= $this->intervalToHalfOpen;
    }

    public function recordSuccess(string $service, CircuitBreakerStorage $storage): void
    {
        $storage->incrementSuccess($service, $this->timeWindow);
    }

    public function recordFailure(string $service, CircuitBreakerStorage $storage): void
    {
        $storage->incrementFailure($service, $this->timeWindow);
    }

    public function getTimeWindow(): int
    {
        return $this->timeWindow;
    }

    public function getIntervalToHalfOpen(): int
    {
        return $this->intervalToHalfOpen;
    }

    public function getMinimumRequests(): int
    {
        return $this->minimumRequests;
    }

    public function getSuccessThreshold(): int
    {
        return $this->successThreshold;
    }

    public function getFailureRateThreshold(): float
    {
        return $this->failureRateThreshold;
    }
}