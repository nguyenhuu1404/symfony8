<?php

namespace App\Dto\User;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateUserRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required.')]
        #[Assert\Email(message: 'Email is not valid.')]
        #[Assert\Length(max: 180, maxMessage: 'Email must not exceed {{ limit }} characters.')]
        public readonly string $email = '',

        #[Assert\NotBlank(message: 'Full name is required.')]
        #[Assert\Length(max: 255, maxMessage: 'Full name must not exceed {{ limit }} characters.')]
        public readonly string $fullName = '',

        // Nullable — không gửi = giữ password cũ, gửi = thay password mới
        #[Assert\Length(min: 8, max: 255, minMessage: 'Password must be at least {{ limit }} characters.', maxMessage: 'Password must not exceed {{ limit }} characters.')]
        public readonly ?string $password = null,

        #[Assert\NotNull(message: 'isActive is required.')]
        public readonly bool $isActive = true,

        // Nullable — null = giữ nguyên roles hiện tại, [] = xoá hết, [1,2] = thay thế
        #[Assert\Type(type: 'array', message: 'roleIds must be an array.')]
        #[Assert\All([
            new Assert\Type(type: 'integer', message: 'Each role ID must be an integer.'),
            new Assert\Positive(message: 'Each role ID must be a positive integer.'),
        ])]
        public readonly ?array $roleIds = null,
    ) {
    }
}
