<?php

namespace App\DataFixtures;

use App\Entity\Permission;
use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RoleFixtures extends Fixture implements DependentFixtureInterface
{
    public const ROLE_ADMIN = 'role_admin';
    public const ROLE_EDITOR = 'role_editor';
    public const ROLE_USER = 'role_user';

    public function load(ObjectManager $manager): void
    {
        // ROLE_ADMIN — full quyền, gán tất cả permission đang có
        $admin = new Role();
        $admin->setName('ROLE_ADMIN');
        $admin->setDescription('Toàn quyền quản trị hệ thống');

        foreach (PermissionFixtures::PERMISSIONS as $data) {
            /** @var Permission $permission */
            $permission = $this->getReference('permission_' . $data['name'], Permission::class);
            $admin->addPermission($permission);
        }

        $manager->persist($admin);
        $this->addReference(self::ROLE_ADMIN, $admin);

        // ROLE_EDITOR — chỉ quản lý user, không đụng tới role/permission
        $editor = new Role();
        $editor->setName('ROLE_EDITOR');
        $editor->setDescription('Quản lý user, không có quyền quản trị hệ thống');

        foreach (['user.view', 'user.create', 'user.edit'] as $permissionName) {
            /** @var Permission $permission */
            $permission = $this->getReference('permission_' . $permissionName, Permission::class);
            $editor->addPermission($permission);
        }

        $manager->persist($editor);
        $this->addReference(self::ROLE_EDITOR, $editor);

        // ROLE_USER — chỉ xem, không có quyền thao tác
        $user = new Role();
        $user->setName('ROLE_USER');
        $user->setDescription('Người dùng thông thường, chỉ có quyền xem cơ bản');

        /** @var Permission $viewPermission */
        $viewPermission = $this->getReference('permission_user.view', Permission::class);
        $user->addPermission($viewPermission);

        $manager->persist($user);
        $this->addReference(self::ROLE_USER, $user);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PermissionFixtures::class,
        ];
    }
}
