<?php

namespace App\Dto\User;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required.')]
        #[Assert\Email(message: 'Email is not valid.')]
        #[Assert\Length(max: 180, maxMessage: 'Email must not exceed {{ limit }} characters.')]
        public readonly string $email = '',

        #[Assert\NotBlank(message: 'Full name is required.')]
        #[Assert\Length(max: 255, maxMessage: 'Full name must not exceed {{ limit }} characters.')]
        public readonly string $fullName = '',

        #[Assert\NotBlank(message: 'Password is required.')]
        #[Assert\Length(min: 8, max: 255, minMessage: 'Password must be at least {{ limit }} characters.', maxMessage: 'Password must not exceed {{ limit }} characters.')]
        public readonly string $password = '',

        #[Assert\NotNull(message: 'isActive is required.')]
        public readonly bool $isActive = true,

        #[Assert\NotBlank(message: 'At least one role is required.')]
        #[Assert\Type(type: 'array', message: 'roleIds must be an array.')]
        #[Assert\Count(min: 1, minMessage: 'At least one role is required.')]
        #[Assert\All([
            new Assert\Type(type: 'integer', message: 'Each role ID must be an integer.'),
            new Assert\Positive(message: 'Each role ID must be a positive integer.'),
        ])]
        public readonly array $roleIds = [],
    ) {
    }
}
