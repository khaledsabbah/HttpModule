<?php

namespace Idaratech\Integrations\CircuitBreaker\Strategy;

use Idaratech\Integrations\CircuitBreaker\Contracts\CircuitBreakerStorage;
use Idaratech\Integrations\CircuitBreaker\Contracts\StrategyInterface;

/**
 * Count-based circuit breaker strategy.
 *
 * Trips the circuit when the absolute failure count exceeds a threshold
 * within a specified time window.
 */
class CountStrategy implements StrategyInterface
{
    public function __construct(
        protected readonly int $timeWindow = 60,
        protected readonly int $failureCountThreshold = 5,
        protected readonly int $intervalToHalfOpen = 30,
        protected readonly int $successThreshold = 3
    ) {}

    public function shouldTrip(string $service, CircuitBreakerStorage $storage): bool
    {
        $failures = $storage->getFailureCount($service);

        return $failures >= $this->failureCountThreshold;
    }

    public function shouldClose(string $service, CircuitBreakerStorage $storage): bool
    {
        $halfOpenSuccesses = $storage->getHalfOpenSuccessCount($service);

        return $halfOpenSuccesses >= $this->successThreshold;
    }

    public function shouldAttemptReset(string $service, CircuitBreakerStorage $storage): bool
    {
        $openedAt = $storage->getOpenedAt($service);

        if ($openedAt === null) {
            return true;
        }

        return (time() - $openedAt) >= $this->intervalToHalfOpen;
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
        return 0; // Count strategy doesn't require minimum requests
    }

    public function getSuccessThreshold(): int
    {
        return $this->successThreshold;
    }

    public function getFailureCountThreshold(): int
    {
        return $this->failureCountThreshold;
    }
}
