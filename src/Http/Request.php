<?php
namespace Idaratech\Integrations\Http;

use Idaratech\Integrations\Contracts\IClient;
use Idaratech\Integrations\Http\Enums\Method;
use Idaratech\Integrations\Http\Exceptions\RequestValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException as LaravelValidationException;

abstract class Request
{
    protected array $headers = [];
    protected array $query = [];
    protected array $body = [];
    protected bool $stopOnFirstFailure = false;

    abstract public function method(): Method;
    public function uri(): string { return ''; }
    public function fullUri(): string { return $this->uri(); }

    public function headers(): array { return $this->headers; }
    public function query(): array { return $this->query; }
    public function body(): array { return $this->body; }
    public function options(): array { return []; }

    public function runBeforeMiddlewares(IClient $client): void { $this->validate(); }
    public function runAfterMiddlewares(IClient $client): void {}

    public function rules(): array { return []; }
    public function messages(): array { return []; }
    public function attributes(): array { return []; }

    public function validationData(): array { return array_merge($this->query(), $this->body()); }

    public function stopOnFirstFailure(bool $value = true): static
    {
        $this->stopOnFirstFailure = $value;
        return $this;
    }

    protected function validate(): void
    {
        $rules = $this->rules();
        if (empty($rules)) return;

        $data = $this->validationData();
        $validator = Validator::make($data, $rules, $this->messages(), $this->attributes());
        if ($this->stopOnFirstFailure) $validator->stopOnFirstFailure();

        try { $validator->validate(); }
        catch (LaravelValidationException $e) {
            $safe = $this->redact($data);
            throw new RequestValidationException('Request validation failed.', $safe, $rules, $e->validator->errors(), 422, $e);
        }
    }

    protected function redact(array $data): array
    {
        $s = ['password','token','access_token','secret','authorization'];
        foreach ($s as $k) if (array_key_exists($k, $data)) $data[$k] = '******';
        return $data;
    }
}
