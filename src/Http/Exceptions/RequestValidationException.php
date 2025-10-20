<?php
namespace Idaratech\Integrations\Http\Exceptions;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\MessageBag;
use Throwable;

class RequestValidationException extends \InvalidArgumentException implements Arrayable
{
    protected array $data;
    protected array $rules;
    protected MessageBag $errors;

    public function __construct(
        string $message,
        array $data,
        array $rules,
        MessageBag $errors,
        int $code = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->data   = $data;
        $this->rules  = $rules;
        $this->errors = $errors;
    }

    public function getData(): array { return $this->data; }
    public function getRules(): array { return $this->rules; }
    public function getErrors(): MessageBag { return $this->errors; }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code'    => $this->getCode(),
            'errors'  => $this->errors->toArray(),
        ];
    }
}
