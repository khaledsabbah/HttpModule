<?php

namespace Idaratech\Integrations\CircuitBreaker\Config;

class CountStrategyConfig extends CircuitBreakerConfig
{
    protected ?int $failureCountThreshold = null;

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
     * @param int $count
     * @return self
     */
    public function failureCountThreshold(int $count): self
    {
        if ($count < 1) {
            throw new \InvalidArgumentException(
                "Failure count threshold must be at least 1, got: {$count}"
            );
        }
        $this->failureCountThreshold = $count;
        return $this;
    }

    /**
     * @return int
     */
    public function getFailureCountThreshold(): int
    {
        if ($this->failureCountThreshold === null) {
            throw new \LogicException(
                'failureCountThreshold not set. Call ->failureCountThreshold() before using this config.'
            );
        }
        return $this->failureCountThreshold;
    }

    /**
     * @return string
     */
    public function getStrategy(): string
    {
        return 'count';
    }
}
