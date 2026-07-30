<?php

namespace App\DataFixtures;

use App\Entity\Permission;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PermissionFixtures extends Fixture
{
    // Danh sách permission theo module — dùng name làm khoá reference
    // để RoleFixtures gọi lại bằng self::PERMISSIONS[...]['name']
    public const PERMISSIONS = [
        // Module: user
        ['name' => 'user.view', 'group' => 'user'],
        ['name' => 'user.create', 'group' => 'user'],
        ['name' => 'user.edit', 'group' => 'user'],
        ['name' => 'user.delete', 'group' => 'user'],

        // Module: role
        ['name' => 'role.view', 'group' => 'role'],
        ['name' => 'role.create', 'group' => 'role'],
        ['name' => 'role.edit', 'group' => 'role'],
        ['name' => 'role.delete', 'group' => 'role'],
        ['name' => 'role.assign_permission', 'group' => 'role'],

        // Module: permission
        ['name' => 'permission.view', 'group' => 'permission'],
        ['name' => 'permission.manage', 'group' => 'permission'],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::PERMISSIONS as $data) {
            $permission = new Permission();
            $permission->setName($data['name']);
            $permission->setGroup($data['group']);

            $manager->persist($permission);

            // Đặt reference để RoleFixtures lấy lại đúng object đã persist,
            // tránh phải query lại DB (tương tự cách bạn seed data phụ thuộc bên NestJS)
            $this->addReference('permission_' . $data['name'], $permission);
        }

        $manager->flush();
    }
}
