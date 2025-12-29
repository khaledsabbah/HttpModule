<?php

namespace Idaratech\Integrations\CircuitBreaker\Config;

class RateStrategyConfig extends CircuitBreakerConfig
{
    protected ?float $failureRateThreshold = null;
    protected ?int $minimumRequests = null;

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
     * @param float $percentage
     * @return self
     */
    public function failureRateThreshold(float $percentage): self
    {
        if ($percentage < 0 || $percentage > 100) {
            throw new \InvalidArgumentException(
                "Failure rate threshold must be between 0 and 100, got: {$percentage}"
            );
        }
        $this->failureRateThreshold = $percentage;
        return $this;
    }

    /**
     * Set the minimum number of requests before evaluating failure rate.
     * Circuit won't trip until this many requests have been made.
     *
     * @param int $count
     * @return self
     */
    public function minimumRequests(int $count): self
    {
        if ($count < 1) {
            throw new \InvalidArgumentException(
                "Minimum requests must be at least 1, got: {$count}"
            );
        }
        $this->minimumRequests = $count;
        return $this;
    }

    public function getFailureRateThreshold(): float
    {
        if ($this->failureRateThreshold === null) {
            throw new \LogicException(
                'failureRateThreshold not set. Call ->failureRateThreshold() before using this config.'
            );
        }
        return $this->failureRateThreshold;
    }

    public function getMinimumRequests(): int
    {
        if ($this->minimumRequests === null) {
            throw new \LogicException(
                'minimumRequests not set. Call ->minimumRequests() before using this config.'
            );
        }
        return $this->minimumRequests;
    }

    public function getStrategy(): string
    {
        return 'rate';
    }
}
