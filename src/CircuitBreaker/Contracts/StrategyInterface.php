<?php

namespace Idaratech\Integrations\CircuitBreaker\Contracts;

interface StrategyInterface
{
    /**
     * Check if the circuit should trip to open state.
     *
     * @param string $service
     * @param CircuitBreakerStorage $storage
     * @return bool
     */
    public function shouldTrip(string $service, CircuitBreakerStorage $storage): bool;

    /**
     * Check if the circuit should transition from half-open to closed.
     *
     * @param string $service
     * @param CircuitBreakerStorage $storage
     * @return bool
     */
    public function shouldClose(string $service, CircuitBreakerStorage $storage): bool;

    /**
     * Check if enough time has passed to transition from open to half-open.
     *
     * @param string $service
     * @param CircuitBreakerStorage $storage
     * @return bool
     */
    public function shouldAttemptReset(string $service, CircuitBreakerStorage $storage): bool;

    /**
     * Record a successful operation.
     *
     * @param string $service
     * @param CircuitBreakerStorage $storage
     * @return void
     */
    public function recordSuccess(string $service, CircuitBreakerStorage $storage): void;

    /**
     * Record a failed operation.
     *
     * @param string $service
     * @param CircuitBreakerStorage $storage
     * @return void
     */
    public function recordFailure(string $service, CircuitBreakerStorage $storage): void;

    /**
     * Get the time window in seconds.
     *
     * @return int
     */
    public function getTimeWindow(): int;

    /**
     * Get the interval to half-open in seconds.
     *
     * @return int
     */
    public function getIntervalToHalfOpen(): int;

    /**
     * Get the minimum requests before evaluation.
     *
     * @return int
     */
    public function getMinimumRequests(): int;

    /**
     * Get the success threshold for half-open state.
     *
     * @return int
     */
    public function getSuccessThreshold(): int;
}