<?php

namespace App\Http\Exception;

use RuntimeException;

/**
 * Throw khi validation fail trong FormRequest.
 * Được bắt bởi ValidationExceptionListener để render JSON 422.
 */
final class ValidationException extends RuntimeException
{
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Validation failed.');
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
