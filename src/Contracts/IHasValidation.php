<?php

namespace Idaratech\Integrations\Contracts;

interface IHasValidation
{
    public function validate(): void;
    public function rules(): array;
}
