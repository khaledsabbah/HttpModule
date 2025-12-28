<?php

namespace Idaratech\Integrations\CircuitBreaker;

use Idaratech\Integrations\CircuitBreaker\Contracts\CircuitBreakerStorage;
use Idaratech\Integrations\CircuitBreaker\Contracts\StrategyInterface;
use Idaratech\Integrations\CircuitBreaker\Enums\CircuitState;
use Idaratech\Integrations\CircuitBreaker\Exceptions\CircuitOpenException;
use Idaratech\Integrations\Logger;
use Throwable;

class CircuitBreaker
{
    /** @var int[] */
    protected array $failureStatusCodes = [500, 502, 503, 504];

    /** @var int[] */
    protected array $ignoredStatusCodes = [];

    public function __construct(
        protected readonly CircuitBreakerStorage $storage,
        protected readonly StrategyInterface $strategy
    ) {}

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

    public function getState(string $service): CircuitState
    {
        return $this->storage->getState($service);
    }

    /**
     * @template T
     * @param callable(): T $callable
     * @param callable(): T|null $fallback
     * @return T
     * @throws CircuitOpenException|Throwable
     */
    public function call(string $service, callable $callable, ?callable $fallback = null): mixed
    {
        if (! $this->isAvailable($service)) {
            return $fallback
                ? $fallback()
                : throw new CircuitOpenException($service, CircuitState::OPEN);
        }

        try {
            $result = $callable();
            $this->success($service);
            return $result;
        } catch (Throwable $e) {
            $this->failure($service);
            throw $e;
        }
    }

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

    public function recordHttpResult(string $service, int $statusCode): void
    {
        $this->isFailureStatusCode($statusCode)
            ? $this->failure($service)
            : $this->success($service);
    }

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

    /** @param int[] $statusCodes */
    public function setFailureStatusCodes(array $statusCodes): self
    {
        $this->failureStatusCodes = $statusCodes;
        return $this;
    }

    /** @param int[] $statusCodes */
    public function setIgnoredStatusCodes(array $statusCodes): self
    {
        $this->ignoredStatusCodes = $statusCodes;
        return $this;
    }

    public function getStorage(): CircuitBreakerStorage
    {
        return $this->storage;
    }

    public function getStrategy(): StrategyInterface
    {
        return $this->strategy;
    }

    protected function transitionTo(string $service, CircuitState $newState): void
    {
        if ($this->getState($service) === $newState) {
            return;
        }

        $this->storage->setState($service, $newState);

        match ($newState) {
            CircuitState::OPEN => $this->onOpen($service),
            CircuitState::HALF_OPEN => $this->onHalfOpen($service),
            CircuitState::CLOSED => $this->onClosed($service),
        };
    }

    protected function onOpen(string $service): void
    {
        $this->storage->setOpenedAt($service, time());
        $this->storage->resetHalfOpenSuccess($service);
        Logger::warning('CIRCUIT_BREAKER_TRIPPED', ['service' => $service]);
    }

    protected function onHalfOpen(string $service): void
    {
        $this->storage->resetHalfOpenSuccess($service);
        Logger::info('CIRCUIT_BREAKER_HALF_OPEN', ['service' => $service]);
    }

    protected function onClosed(string $service): void
    {
        $this->storage->reset($service);
        Logger::info('CIRCUIT_BREAKER_CLOSED', ['service' => $service]);
    }
}