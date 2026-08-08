<?php

namespace App\Http\Mapper;

use App\Entity\Role;

final class RoleMapper
{
    public static function toArray(Role $role): array
    {
        return [
            'id'          => $role->getId(),
            'name'        => $role->getName(),
            'description' => $role->getDescription(),
            'permissions' => array_map(
                static fn ($permission) => [
                    'id'   => $permission->getId(),
                    'name' => $permission->getName(),
                ],
                $role->getPermissions()->toArray(),
            ),
            'created_at'  => $role->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at'  => $role->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param iterable<Role> $roles
     */
    public static function collection(iterable $roles): array
    {
        $result = [];
        foreach ($roles as $role) {
            $result[] = self::toArray($role);
        }

        return $result;
    }
}
