<?php

namespace Idaratech\Integrations\Contracts;

use App\Libs\Integrations\Http\Request;

interface IRequestMiddleware
{

    public function handle(IRequest $request, IClient $client): Request;

}
