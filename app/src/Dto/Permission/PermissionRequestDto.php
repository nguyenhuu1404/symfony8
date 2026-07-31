<?php

namespace App\Dto\Permission;

use Symfony\Component\Validator\Constraints as Assert;

final class PermissionRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Name is required.')]
        #[Assert\Length(max: 150, maxMessage: 'Name must not exceed {{ limit }} characters.')]
        public readonly string $name = '',

        #[Assert\NotBlank(message: 'Group is required.')]
        #[Assert\Length(max: 100, maxMessage: 'Group must not exceed {{ limit }} characters.')]
        public readonly string $group = '',
    ) {
    }
}
