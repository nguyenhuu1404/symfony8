<?php

namespace App\Http\Request;

use Symfony\Component\HttpFoundation\Request;

/**
 * Base class cho tất cả Form Requests — tương tự Laravel's FormRequest.
 *
 * Cách dùng:
 *   1. Extend class này
 *   2. Khai báo properties với #[Assert\*] constraints
 *   3. Implement fromRequest() để map raw input → properties
 *   4. Type-hint thẳng vào Controller method — Symfony tự inject & validate
 *
 * Nếu validation fail, FormRequestValueResolver throw ValidationException
 * và ValidationExceptionListener render JSON 422 tự động.
 */
abstract class FormRequest
{
    /**
     * Build instance từ raw Request.
     * Override để map JSON body / query params / route attributes vào properties.
     */
    abstract public static function fromRequest(Request $request): static;
}
