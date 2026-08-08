<?php

namespace App\Dto\Role;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateRoleRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Name is required.')]
        #[Assert\Length(max: 100, maxMessage: 'Name must not exceed {{ limit }} characters.')]
        public readonly string $name = '',

        #[Assert\Length(max: 255, maxMessage: 'Description must not exceed {{ limit }} characters.')]
        public readonly ?string $description = null,

        // Nullable — nếu không gửi, permissions hiện tại giữ nguyên
        #[Assert\Type(type: 'array', message: 'permission_ids must be an array.')]
        #[Assert\All([
            new Assert\Type(type: 'integer', message: 'Each permission ID must be an integer.'),
            new Assert\Positive(message: 'Each permission ID must be a positive integer.'),
        ])]
        public readonly ?array $permissionIds = null,
    ) {
    }
}
