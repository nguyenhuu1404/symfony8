<?php

namespace App\DataFixtures;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    // Fixture cũng là service bình thường -> autowire được UserPasswordHasherInterface
    // để hash password đúng chuẩn, không lưu plaintext (giống cách bạn dùng bcrypt bên NestJS)
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setFullName('System Admin');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin@123'));
        $admin->setIsActive(true);
        $admin->addRoleEntity($this->getReference(RoleFixtures::ROLE_ADMIN, Role::class));
        $manager->persist($admin);

        $editor = new User();
        $editor->setEmail('editor@example.com');
        $editor->setFullName('Content Editor');
        $editor->setPassword($this->passwordHasher->hashPassword($editor, 'Editor@123'));
        $editor->setIsActive(true);
        $editor->addRoleEntity($this->getReference(RoleFixtures::ROLE_EDITOR, Role::class));
        $manager->persist($editor);

        $normalUser = new User();
        $normalUser->setEmail('user@example.com');
        $normalUser->setFullName('Normal User');
        $normalUser->setPassword($this->passwordHasher->hashPassword($normalUser, 'User@123'));
        $normalUser->setIsActive(true);
        $normalUser->addRoleEntity($this->getReference(RoleFixtures::ROLE_USER, Role::class));
        $manager->persist($normalUser);

        // Thêm 1 user bị khoá để test luồng "isActive = false" (VD: chặn login)
        $inactiveUser = new User();
        $inactiveUser->setEmail('inactive@example.com');
        $inactiveUser->setFullName('Inactive User');
        $inactiveUser->setPassword($this->passwordHasher->hashPassword($inactiveUser, 'Inactive@123'));
        $inactiveUser->setIsActive(false);
        $inactiveUser->addRoleEntity($this->getReference(RoleFixtures::ROLE_USER, Role::class));
        $manager->persist($inactiveUser);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RoleFixtures::class,
        ];
    }
}
