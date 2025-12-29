<?php

namespace Idaratech\Integrations\CircuitBreaker\Exceptions;

use Exception;
use Idaratech\Integrations\CircuitBreaker\Enums\CircuitState;

class CircuitOpenException extends Exception
{
    protected string $service;
    protected CircuitState $state;

    /**
     * @param string $service
     * @param CircuitState $state
     * @param Exception|null $previous
     */
    public function __construct(
        string $service,
        CircuitState $state = CircuitState::OPEN,
        ?Exception $previous = null
    ) {
        $this->service = $service;
        $this->state = $state;

        $message = "Circuit breaker is open for service '{$service}'.";

        parent::__construct($message, 503, $previous);
    }

    public function getService(): string
    {
        return $this->service;
    }

    public function getState(): CircuitState
    {
        return $this->state;
    }
}
