<?php

namespace Idaratech\Integrations\CircuitBreaker\Enums;

enum CircuitState: string
{
    case CLOSED = 'closed';
    case OPEN = 'open';
    case HALF_OPEN = 'half_open';

    public function isClosed(): bool
    {
        return $this === self::CLOSED;
    }

    public function isHalfOpen(): bool
    {
        return $this === self::HALF_OPEN;
    }
}
