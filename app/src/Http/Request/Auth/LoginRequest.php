<?php

namespace App\Http\Request\Auth;

use App\Http\Request\FormRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

final class LoginRequest extends FormRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required.')]
        #[Assert\Email(message: 'Invalid email format.')]
        public readonly string $email = '',

        #[Assert\NotBlank(message: 'Password is required.')]
        #[Assert\Length(min: 6, minMessage: 'Password must be at least {{ limit }} characters.')]
        public readonly string $password = '',
    ) {}

    public static function fromRequest(Request $request): static
    {
        $data = json_decode($request->getContent(), true) ?? [];

        return new static(
            email:    trim((string) ($data['email'] ?? '')),
            password: (string) ($data['password'] ?? ''),
        );
    }
}
