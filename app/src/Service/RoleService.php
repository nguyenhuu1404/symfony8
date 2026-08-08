<?php

namespace App\Service;

use App\Dto\Role\CreateRoleRequestDto;
use App\Dto\Role\UpdateRoleRequestDto;
use App\Entity\Permission;
use App\Entity\Role;
use App\Repository\PermissionRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class RoleService
{
    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return Role[]
     */
    public function list(): array
    {
        return $this->roleRepository->findAll();
    }

    public function create(CreateRoleRequestDto $dto): Role
    {
        $this->assertNameNotTaken($dto->name);

        $role = new Role();
        $role->setName($dto->name);
        $role->setDescription($dto->description);

        $permissions = $this->resolvePermissions($dto->permissionIds);
        foreach ($permissions as $permission) {
            $role->addPermission($permission);
        }

        $this->entityManager->persist($role);
        $this->entityManager->flush();

        return $role;
    }

    public function update(Role $role, UpdateRoleRequestDto $dto): Role
    {
        if ($dto->name !== $role->getName()) {
            $this->assertNameNotTaken($dto->name);
        }

        $role->setName($dto->name);
        $role->setDescription($dto->description);

        // Chỉ sync permissions nếu client gửi permission_ids
        // null = giữ nguyên, [] = xoá hết, [1,2] = thay thế toàn bộ
        if ($dto->permissionIds !== null) {
            $this->syncPermissions($role, $dto->permissionIds);
        }

        $this->entityManager->flush();

        return $role;
    }

    public function delete(Role $role): void
    {
        $this->entityManager->remove($role);
        $this->entityManager->flush();
    }

    private function assertNameNotTaken(string $name): void
    {
        if ($this->roleRepository->findOneBy(['name' => $name]) !== null) {
            throw new ConflictHttpException(sprintf('Role "%s" already exists.', $name));
        }
    }

    /**
     * @param int[] $ids
     * @return Permission[]
     */
    private function resolvePermissions(array $ids): array
    {
        $permissions = [];
        foreach ($ids as $id) {
            $permission = $this->permissionRepository->find($id);
            if ($permission === null) {
                throw new UnprocessableEntityHttpException(
                    sprintf('Permission with ID %d not found.', $id)
                );
            }
            $permissions[] = $permission;
        }

        return $permissions;
    }

    /**
     * Replace tất cả permissions của role bằng danh sách mới.
     *
     * @param int[] $newIds
     */
    private function syncPermissions(Role $role, array $newIds): void
    {
        // Xoá toàn bộ permissions hiện tại
        foreach ($role->getPermissions()->toArray() as $existing) {
            $role->removePermission($existing);
        }

        // Gán lại theo danh sách mới
        $permissions = $this->resolvePermissions($newIds);
        foreach ($permissions as $permission) {
            $role->addPermission($permission);
        }
    }
}
