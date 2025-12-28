<?php

namespace Idaratech\Integrations\Contracts;

use Idaratech\Integrations\CircuitBreaker\CircuitBreaker;

interface IClient
{
    public function do(IRequest $request): IResponse;

    public function process(IRequest $request): IDto;

    public function withCircuitBreaker(CircuitBreaker $circuitBreaker): self;
}
