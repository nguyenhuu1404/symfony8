<?php

namespace App\Http\Mapper;

use App\Entity\Permission;

final class PermissionMapper
{
    public static function toArray(Permission $permission): array
    {
        return [
            'id' => $permission->getId(),
            'name' => $permission->getName(),
            'group' => $permission->getGroup(),
            'created_at' => $permission->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $permission->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param iterable<Permission> $permissions
     */
    public static function collection(iterable $permissions): array
    {
        $result = [];
        foreach ($permissions as $permission) {
            $result[] = self::toArray($permission);
        }

        return $result;
    }
}
