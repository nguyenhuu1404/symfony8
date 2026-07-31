<?php

namespace App\Http\Mapper;

use App\Entity\User;

/**
 * Mapper thuần PHP — giống hệt cách bạn viết mapper bên NestJS
 * (VD: UserResponseDto.fromEntity(user) hoặc @nestjs/mapper).
 * Không Attribute, không Listener, không Reflection — đọc là hiểu ngay.
 */
final class UserMapper
{
    public static function toArray(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'full_name' => $user->getFullName(),
            'roles' => $user->getRoles(),
        ];
    }
}
