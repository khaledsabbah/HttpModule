<?php

namespace Idaratech\Integrations\Contracts;


interface IResponse
{
    public function request(): IRequest;
    public function json(string $key = null, mixed $default = null): mixed;

    public function statusCode(): int;
    public function successful(): bool;
    public function failed(): bool;
    public function body(): string;
    public function reason(): string;
}
