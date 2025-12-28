<?php

namespace Idaratech\Integrations\CircuitBreaker\Contracts;

interface StrategyInterface
{
    /**
     * Check if the circuit should trip to open state.
     */
    public function shouldTrip(string $service, CircuitBreakerStorage $storage): bool;

    /**
     * Check if the circuit should transition from half-open to closed.
     */
    public function shouldClose(string $service, CircuitBreakerStorage $storage): bool;

    /**
     * Check if enough time has passed to transition from open to half-open.
     */
    public function shouldAttemptReset(string $service, CircuitBreakerStorage $storage): bool;

    /**
     * Record a successful operation.
     */
    public function recordSuccess(string $service, CircuitBreakerStorage $storage): void;

    /**
     * Record a failed operation.
     */
    public function recordFailure(string $service, CircuitBreakerStorage $storage): void;

    /**
     * Get the time window in seconds.
     */
    public function getTimeWindow(): int;

    /**
     * Get the interval to half-open in seconds.
     */
    public function getIntervalToHalfOpen(): int;

    /**
     * Get the minimum requests before evaluation.
     */
    public function getMinimumRequests(): int;

    /**
     * Get the success threshold for half-open state.
     */
    public function getSuccessThreshold(): int;
}
