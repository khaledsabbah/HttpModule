<?php

namespace App\Libs\Integrations\Http\Contracts;

use App\Libs\Integrations\Http\Request;

interface IRequestMiddleware
{

    public function handle(IRequest $request, IClient $client): Request;

}
