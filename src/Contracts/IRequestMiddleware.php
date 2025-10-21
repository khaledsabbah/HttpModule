<?php

namespace Idaratech\Integrations\Contracts;

use Idaratech\Integrations\Http\Request;

interface IRequestMiddleware
{

    public function handle(IRequest $request, IClient $client): Request;

}
