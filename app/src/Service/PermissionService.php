<?php

namespace App\Service;

use App\Dto\Permission\PermissionRequestDto;
use App\Entity\Permission;
use App\Repository\PermissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class PermissionService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return Permission[]
     */
    public function list(): array
    {
        return $this->permissionRepository->findAll();
    }

    public function create(PermissionRequestDto $dto): Permission
    {
        $this->assertNameNotTaken($dto->name);

        $permission = new Permission();
        $permission->setName($dto->name);
        $permission->setGroup($dto->group);

        $this->entityManager->persist($permission);
        $this->entityManager->flush();

        return $permission;
    }

    public function update(Permission $permission, PermissionRequestDto $dto): Permission
    {
        if ($dto->name !== $permission->getName()) {
            $this->assertNameNotTaken($dto->name);
        }

        $permission->setName($dto->name);
        $permission->setGroup($dto->group);

        $this->entityManager->flush();

        return $permission;
    }

    public function delete(Permission $permission): void
    {
        $this->entityManager->remove($permission);
        $this->entityManager->flush();
    }

    private function assertNameNotTaken(string $name): void
    {
        if ($this->permissionRepository->findByName($name) !== null) {
            throw new ConflictHttpException(sprintf('Permission "%s" already exists.', $name));
        }
    }
}
