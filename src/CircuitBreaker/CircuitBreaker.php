<?php

namespace Idaratech\Integrations\CircuitBreaker;

use Carbon\Carbon;
use Idaratech\Integrations\CircuitBreaker\Contracts\CircuitBreakerStorage;
use Idaratech\Integrations\CircuitBreaker\Contracts\StrategyInterface;
use Idaratech\Integrations\CircuitBreaker\Enums\CircuitState;
use Idaratech\Integrations\Logger;
use Psr\SimpleCache\InvalidArgumentException;

class CircuitBreaker
{
    /** @var int[] */
    protected array $failureStatusCodes = [500, 502, 503, 504];

    /** @var int[] */
    protected array $ignoredStatusCodes = [];

    /**
     * @param CircuitBreakerStorage $storage
     * @param StrategyInterface $strategy
     */
    public function __construct(
        protected readonly CircuitBreakerStorage $storage,
        protected readonly StrategyInterface $strategy
    ) {}

    /**
     * @param string $service
     * @return bool
     * @throws InvalidArgumentException
     */
    public function isAvailable(string $service): bool
    {
        $state = $this->getState($service);

        if ($state->isClosed() || $state->isHalfOpen()) {
            return true;
        }

        if ($this->strategy->shouldAttemptReset($service, $this->storage)) {
            $this->transitionTo($service, CircuitState::HALF_OPEN);
            return true;
        }

        return false;
    }

    /**
     * @param string $service
     * @return CircuitState
     * @throws InvalidArgumentException
     */
    public function getState(string $service): CircuitState
    {
        return $this->storage->getState($service);
    }

    /**
     * @param string $service
     * @return void
     * @throws InvalidArgumentException
     */
    public function success(string $service): void
    {
        $state = $this->getState($service);

        if ($state->isHalfOpen()) {
            $this->storage->incrementHalfOpenSuccess($service);

            if ($this->strategy->shouldClose($service, $this->storage)) {
                $this->transitionTo($service, CircuitState::CLOSED);
            }
            return;
        }

        $this->strategy->recordSuccess($service, $this->storage);
    }

    /**
     * @param string $service
     * @return void
     * @throws InvalidArgumentException
     */
    public function failure(string $service): void
    {
        $state = $this->getState($service);

        if ($state->isHalfOpen()) {
            $this->transitionTo($service, CircuitState::OPEN);
            return;
        }

        $this->strategy->recordFailure($service, $this->storage);

        if ($this->strategy->shouldTrip($service, $this->storage)) {
            $this->transitionTo($service, CircuitState::OPEN);
        }
    }

    /**
     * @param string $service
     * @param int $statusCode
     * @return void
     * @throws InvalidArgumentException
     */
    public function recordHttpResult(string $service, int $statusCode): void
    {
        $this->isFailureStatusCode($statusCode)
            ? $this->failure($service)
            : $this->success($service);
    }

    /**
     * @param int $statusCode
     * @return bool
     */
    public function isFailureStatusCode(int $statusCode): bool
    {
        if (in_array($statusCode, $this->ignoredStatusCodes, true)) {
            return false;
        }

        if (! empty($this->failureStatusCodes)) {
            return in_array($statusCode, $this->failureStatusCodes, true);
        }

        return $statusCode >= 500;
    }

    /**
     * @param int[] $statusCodes
     * @return self
     */
    public function setFailureStatusCodes(array $statusCodes): self
    {
        $this->failureStatusCodes = $statusCodes;
        return $this;
    }

    /**
     * @param int[] $statusCodes
     * @return self
     */
    public function setIgnoredStatusCodes(array $statusCodes): self
    {
        $this->ignoredStatusCodes = $statusCodes;
        return $this;
    }

    /**
     * @param string $service
     * @param CircuitState $newState
     * @return void
     * @throws InvalidArgumentException
     */
    protected function transitionTo(string $service, CircuitState $newState): void
    {
        if ($this->getState($service) === $newState) {
            return;
        }

        // Calculate TTL for OPEN state (auto-cleanup after 1 hour for abandoned circuits)
        $ttl = $newState === CircuitState::OPEN
            ? 3600
            : null;

        $this->storage->setState($service, $newState, $ttl);

        match ($newState) {
            CircuitState::OPEN => $this->onOpen($service),
            CircuitState::HALF_OPEN => $this->onHalfOpen($service),
            CircuitState::CLOSED => $this->onClosed($service),
        };
    }

    /**
     * @param string $service
     * @return void
     */
    protected function onOpen(string $service): void
    {
        $this->storage->setOpenedAt($service, time());
        $this->storage->resetHalfOpenSuccess($service);

        Logger::warning('CIRCUIT_BREAKER_TRIPPED', [
            'service' => $service,
            'failures' => $this->storage->getFailureCount($service),
            'successes' => $this->storage->getSuccessCount($service),
            'timestamp' => Carbon::now()->toDateTimeString(),
        ]);
    }

    /**
     * @param string $service
     * @return void
     */
    protected function onHalfOpen(string $service): void
    {
        $this->storage->resetHalfOpenSuccess($service);

        Logger::info('CIRCUIT_BREAKER_HALF_OPEN', [
            'service' => $service,
            'message' => 'Testing recovery',
            'timestamp' => Carbon::now()->toDateTimeString(),
        ]);
    }

    /**
     * @param string $service
     * @return void
     */
    protected function onClosed(string $service): void
    {
        $this->storage->reset($service);

        Logger::info('CIRCUIT_BREAKER_CLOSED', [
            'service' => $service,
            'message' => 'Service recovered',
            'timestamp' => Carbon::now()->toDateTimeString(),
        ]);
    }
}