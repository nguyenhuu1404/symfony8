<?php

namespace App\Http\Mapper;

use App\Entity\User;

/**
 * Mapper thuần PHP — chỉ expose field cần thiết.
 * password KHÔNG BAO GIỜ xuất hiện trong response.
 */
final class UserMapper
{
    public static function toArray(User $user): array
    {
        return [
            'id'         => $user->getId(),
            'email'      => $user->getEmail(),
            'full_name'  => $user->getFullName(),
            'is_active'  => $user->isActive(),
            'roles'      => array_map(
                static fn ($role) => [
                    'id'   => $role->getId(),
                    'name' => $role->getName(),
                ],
                $user->getRoleEntities()->toArray(),
            ),
            'created_at' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $user->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param iterable<User> $users
     */
    public static function collection(iterable $users): array
    {
        $result = [];
        foreach ($users as $user) {
            $result[] = self::toArray($user);
        }

        return $result;
    }
}
