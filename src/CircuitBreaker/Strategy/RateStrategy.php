<?php

namespace Idaratech\Integrations\CircuitBreaker\Strategy;

use Idaratech\Integrations\CircuitBreaker\Contracts\CircuitBreakerStorage;
use Idaratech\Integrations\CircuitBreaker\Contracts\StrategyInterface;

class RateStrategy implements StrategyInterface
{
    /**
     * @param int $timeWindow
     * @param float $failureRateThreshold
     * @param int $minimumRequests
     * @param int $intervalToHalfOpen
     * @param int $successThreshold
     */
    public function __construct(
        protected readonly int $timeWindow = 60,
        protected readonly float $failureRateThreshold = 50.0,
        protected readonly int $minimumRequests = 10,
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
        $totalRequests = $storage->getRequestCount($service);

        if ($totalRequests < $this->minimumRequests) {
            return false;
        }

        $failureRate = ($storage->getFailureCount($service) / $totalRequests) * 100;

        return $failureRate >= $this->failureRateThreshold;
    }

    /**
     * @param string $service
     * @param CircuitBreakerStorage $storage
     * @return bool
     */
    public function shouldClose(string $service, CircuitBreakerStorage $storage): bool
    {
        return $storage->getHalfOpenSuccessCount($service) >= $this->successThreshold;
    }

    /**
     * @param string $service
     * @param CircuitBreakerStorage $storage
     * @return bool
     */
    public function shouldAttemptReset(string $service, CircuitBreakerStorage $storage): bool
    {
        $openedAt = $storage->getOpenedAt($service);

        return $openedAt === null || (time() - $openedAt) >= $this->intervalToHalfOpen;
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
        return $this->minimumRequests;
    }

    /**
     * @return int
     */
    public function getSuccessThreshold(): int
    {
        return $this->successThreshold;
    }

    /**
     * @return float
     */
    public function getFailureRateThreshold(): float
    {
        return $this->failureRateThreshold;
    }
}