<?php

namespace App\Libs\Integrations\Http\Contracts;

interface IHasValidation
{
    public function validate(): void;
    public function rules(): array;
}
