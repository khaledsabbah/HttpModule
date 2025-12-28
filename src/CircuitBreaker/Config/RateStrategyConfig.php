<?php

namespace Idaratech\Integrations\CircuitBreaker\Config;

/**
 * Configuration for rate-based circuit breaker strategy.
 *
 * Trips the circuit when the failure rate (percentage) exceeds a threshold
 * within a specified time window.
 */
class RateStrategyConfig extends CircuitBreakerConfig
{
    protected float $failureRateThreshold = 50.0;
    protected int $minimumRequests = 10;

    /**
     * Create a new rate strategy configuration instance.
     *
     * @return self
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Set the failure rate threshold percentage (0-100).
     * Circuit trips when failure rate exceeds this percentage.
     *
     * @param float $percentage Failure rate percentage
     * @return self
     */
    public function failureRateThreshold(float $percentage): self
    {
        $this->failureRateThreshold = $percentage;
        return $this;
    }

    /**
     * Set the minimum number of requests before evaluating failure rate.
     * Circuit won't trip until this many requests have been made.
     *
     * @param int $count Minimum requests
     * @return self
     */
    public function minimumRequests(int $count): self
    {
        $this->minimumRequests = $count;
        return $this;
    }

    public function getFailureRateThreshold(): float
    {
        return $this->failureRateThreshold;
    }

    public function getMinimumRequests(): int
    {
        return $this->minimumRequests;
    }

    public function getStrategy(): string
    {
        return 'rate';
    }
}
