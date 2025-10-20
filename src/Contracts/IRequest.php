<?php

namespace Idaratech\Integrations\Contracts;

use Idaratech\Integrations\Http\Enums\ContentType;
use Idaratech\Integrations\Http\Enums\Method;

interface IRequest
{

    public function runBeforeMiddlewares(IClient $client): void;
    public function runAfterMiddlewares(IClient $client): void;
    public function method(): Method;
    public function body(): array;
    public function headers(): array;
    public function query(): array;
    public function contentType(): ?ContentType;
    public function uri(): string;
    public function baseUrl(): string;
    public function fullUri(): string;

}
