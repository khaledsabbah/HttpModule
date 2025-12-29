<?php

namespace Idaratech\Integrations\CircuitBreaker\Contracts;

use Idaratech\Integrations\CircuitBreaker\Enums\CircuitState;
use Psr\SimpleCache\InvalidArgumentException;

interface CircuitBreakerStorage
{
    /**
     * Get the current state of the circuit.
     * @param string $service
     * @return CircuitState
     * @throws InvalidArgumentException
     */
    public function getState(string $service): CircuitState;

    /**
     * Set the state of the circuit.
     * @param string $service
     * @param CircuitState $state
     * @param int|null $ttl
     */
    public function setState(string $service, CircuitState $state, ?int $ttl = null): void;

    /**
     * Increment the failure count for a service.
     * @param string $service
     * @param int $timeWindow
     */
    public function incrementFailure(string $service, int $timeWindow): int;

    /**
     * Increment the success count for a service.
     * @param string $service
     * @param int $timeWindow
     */
    public function incrementSuccess(string $service, int $timeWindow): int;

    /**
     * Get the failure count for a service.
     * @param string $service
     */
    public function getFailureCount(string $service): int;

    /**
     * Get the success count for a service.
     * @param string $service
     */
    public function getSuccessCount(string $service): int;

    /**
     * Get the total request count (failures + successes).
     * @param string $service
     */
    public function getRequestCount(string $service): int;

    /**
     * Reset all circuit breaker state for a service.
     * @param string $service
     */
    public function reset(string $service): void;

    /**
     * Get the timestamp when the circuit was opened.
     * @param string $service
     */
    public function getOpenedAt(string $service): ?int;

    /**
     * Set the timestamp when the circuit was opened.
     * @param string $service
     * @param int $timestamp
     */
    public function setOpenedAt(string $service, int $timestamp): void;

    /**
     * Get the success count in half-open state.
     * @param string $service
     */
    public function getHalfOpenSuccessCount(string $service): int;

    /**
     * Increment the success count in half-open state.
     * @param string $service
     */
    public function incrementHalfOpenSuccess(string $service): int;

    /**
     * Reset the success count in half-open state.
     * @param string $service
     */
    public function resetHalfOpenSuccess(string $service): void;
}
