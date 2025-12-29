<?php

namespace Idaratech\Integrations\CircuitBreaker\Strategy;

use Idaratech\Integrations\CircuitBreaker\Contracts\CircuitBreakerStorage;
use Idaratech\Integrations\CircuitBreaker\Contracts\StrategyInterface;

class CountStrategy implements StrategyInterface
{
    /**
     * @param int $timeWindow
     * @param int $failureCountThreshold
     * @param int $intervalToHalfOpen
     * @param int $successThreshold
     */
    public function __construct(
        protected readonly int $timeWindow = 60,
        protected readonly int $failureCountThreshold = 5,
        protected readonly int $intervalToHalfOpen = 30,
        protected readonly int $successThreshold = 3
    ) {}

    /**
     * @param string $service
     * @param CircuitBreakerStorage $storage
     * @return bool
     */
    public function shouldTrip(string $service, CircuitBreakerStorage $storage): bool
    {
        $failures = $storage->getFailureCount($service);

        return $failures >= $this->failureCountThreshold;
    }

    /**
     * @param string $service
     * @param CircuitBreakerStorage $storage
     * @return bool
     */
    public function shouldClose(string $service, CircuitBreakerStorage $storage): bool
    {
        $halfOpenSuccesses = $storage->getHalfOpenSuccessCount($service);

        return $halfOpenSuccesses >= $this->successThreshold;
    }

    /**
     * @param string $service
     * @param CircuitBreakerStorage $storage
     * @return bool
     */
    public function shouldAttemptReset(string $service, CircuitBreakerStorage $storage): bool
    {
        $openedAt = $storage->getOpenedAt($service);

        if ($openedAt === null) {
            return true;
        }

        return (time() - $openedAt) >= $this->intervalToHalfOpen;
    }

    /**
     * @param string $service
     * @param CircuitBreakerStorage $storage
     * @return void
     */
    public function recordSuccess(string $service, CircuitBreakerStorage $storage): void
    {
        $storage->incrementSuccess($service, $this->timeWindow);
    }

    /**
     * @param string $service
     * @param CircuitBreakerStorage $storage
     * @return void
     */
    public function recordFailure(string $service, CircuitBreakerStorage $storage): void
    {
        $storage->incrementFailure($service, $this->timeWindow);
    }

    /**
     * @return int
     */
    public function getTimeWindow(): int
    {
        return $this->timeWindow;
    }

    /**
     * @return int
     */
    public function getIntervalToHalfOpen(): int
    {
        return $this->intervalToHalfOpen;
    }

    /**
     * @return int
     */
    public function getMinimumRequests(): int
    {
        return 0; // Count strategy doesn't require minimum requests
    }

    /**
     * @return int
     */
    public function getSuccessThreshold(): int
    {
        return $this->successThreshold;
    }

    /**
     * @return int
     */
    public function getFailureCountThreshold(): int
    {
        return $this->failureCountThreshold;
    }
}