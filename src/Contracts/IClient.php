<?php

namespace Idaratech\Integrations\Contracts;

interface IClient
{
    public function do(IRequest $request): IResponse;

    public function process(IRequest $request): IDto;

}
