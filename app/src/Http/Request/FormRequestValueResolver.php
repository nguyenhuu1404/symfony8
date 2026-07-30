<?php

namespace App\Http\Request;

use App\Http\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tự động resolve và validate bất kỳ argument nào là subclass của FormRequest.
 *
 * Flow:
 *   Controller nhận FormRequest argument
 *   → Symfony gọi resolver này
 *   → resolver gọi fromRequest() để map input
 *   → validate với Symfony Validator
 *   → trả về instance nếu hợp lệ, throw ValidationException nếu không
 */
final class FormRequestValueResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {}

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $argument->getType();

        // Chỉ xử lý subclass của FormRequest
        if ($type === null || !is_subclass_of($type, FormRequest::class)) {
            return [];
        }

        /** @var class-string<FormRequest> $type */
        $formRequest = $type::fromRequest($request);

        $violations = $this->validator->validate($formRequest);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $field          = ltrim($violation->getPropertyPath(), '.');
                $errors[$field] = $violation->getMessage();
            }
            throw new ValidationException($errors);
        }

        yield $formRequest;
    }
}
