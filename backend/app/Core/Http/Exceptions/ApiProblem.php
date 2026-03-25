<?php

namespace App\Core\Http\Exceptions;

use Exception;

class ApiProblem extends Exception
{
    public function __construct(
        string $message,
        protected int $status = 422,
        protected string $errorCode = 'api_problem',
        protected array $errors = [],
        protected array $meta = [],
        int $code = 0,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function meta(): array
    {
        return $this->meta;
    }
}
