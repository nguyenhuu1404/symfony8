<?php

namespace App\Dto\Role;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateRoleRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Name is required.')]
        #[Assert\Length(max: 100, maxMessage: 'Name must not exceed {{ limit }} characters.')]
        public readonly string $name = '',

        #[Assert\Length(max: 255, maxMessage: 'Description must not exceed {{ limit }} characters.')]
        public readonly ?string $description = null,

        // Bắt buộc khi create — role phải có ít nhất 1 permission
        #[Assert\NotBlank(message: 'At least one permission is required.')]
        #[Assert\Type(type: 'array', message: 'permission_ids must be an array.')]
        #[Assert\Count(min: 1, minMessage: 'At least one permission is required.')]
        #[Assert\All([
            new Assert\Type(type: 'integer', message: 'Each permission ID must be an integer.'),
            new Assert\Positive(message: 'Each permission ID must be a positive integer.'),
        ])]
        public readonly array $permissionIds = [],
    ) {
    }
}
