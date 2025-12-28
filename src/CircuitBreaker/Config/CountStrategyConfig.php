<?php

namespace Idaratech\Integrations\CircuitBreaker\Config;

/**
 * Configuration for count-based circuit breaker strategy.
 *
 * Trips the circuit when the absolute failure count exceeds a threshold
 * within a specified time window.
 */
class CountStrategyConfig extends CircuitBreakerConfig
{
    protected int $failureCountThreshold = 5;

    /**
     * Create a new count strategy configuration instance.
     *
     * @return self
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Set the failure count threshold.
     * Circuit trips when this many failures occur within the time window.
     *
     * @param int $count Number of failures to trip circuit
     * @return self
     */
    public function failureCountThreshold(int $count): self
    {
        $this->failureCountThreshold = $count;
        return $this;
    }

    public function getFailureCountThreshold(): int
    {
        return $this->failureCountThreshold;
    }

    public function getStrategy(): string
    {
        return 'count';
    }
}
