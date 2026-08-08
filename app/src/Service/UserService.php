<?php

namespace App\Service;

use App\Dto\User\CreateUserRequestDto;
use App\Dto\User\UpdateUserRequestDto;
use App\Entity\Role;
use App\Entity\User;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @return User[]
     */
    public function list(): array
    {
        return $this->userRepository->findAll();
    }

    public function create(CreateUserRequestDto $dto): User
    {
        $this->assertEmailNotTaken($dto->email);

        $user = new User();
        $user->setEmail($dto->email);
        $user->setFullName($dto->fullName);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
        $user->setIsActive($dto->isActive);

        foreach ($this->resolveRoles($dto->roleIds) as $role) {
            $user->addRoleEntity($role);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function update(User $user, UpdateUserRequestDto $dto): User
    {
        if ($dto->email !== $user->getEmail()) {
            $this->assertEmailNotTaken($dto->email);
        }

        $user->setEmail($dto->email);
        $user->setFullName($dto->fullName);
        $user->setIsActive($dto->isActive);

        // Chỉ hash & set password mới nếu client gửi
        if ($dto->password !== null) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
        }

        // null = giữ nguyên, [] = xoá hết, [1,2] = thay thế toàn bộ
        if ($dto->roleIds !== null) {
            $this->syncRoles($user, $dto->roleIds);
        }

        $this->entityManager->flush();

        return $user;
    }

    public function delete(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    private function assertEmailNotTaken(string $email): void
    {
        if ($this->userRepository->findOneBy(['email' => $email]) !== null) {
            throw new ConflictHttpException(sprintf('Email "%s" is already taken.', $email));
        }
    }

    /**
     * @param int[] $ids
     * @return Role[]
     */
    private function resolveRoles(array $ids): array
    {
        $roles = [];
        foreach ($ids as $id) {
            $role = $this->roleRepository->find($id);
            if ($role === null) {
                throw new UnprocessableEntityHttpException(
                    sprintf('Role with ID %d not found.', $id)
                );
            }
            $roles[] = $role;
        }

        return $roles;
    }

    /**
     * Replace toàn bộ roles của user bằng danh sách mới.
     *
     * @param int[] $newIds
     */
    private function syncRoles(User $user, array $newIds): void
    {
        foreach ($user->getRoleEntities()->toArray() as $existing) {
            $user->removeRoleEntity($existing);
        }

        foreach ($this->resolveRoles($newIds) as $role) {
            $user->addRoleEntity($role);
        }
    }
}
