<?php

namespace App\Controller\Api\V1;

use App\Dto\Role\CreateRoleRequestDto;
use App\Dto\Role\UpdateRoleRequestDto;
use App\Entity\Role;
use App\Http\Mapper\RoleMapper;
use App\Service\RoleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/roles', name: 'api_v1_roles_')]
class RoleController extends AbstractController
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('role.view')]
    public function index(): array
    {
        return RoleMapper::collection($this->roleService->list());
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('role.view')]
    public function show(Role $role): array
    {
        return RoleMapper::toArray($role);
    }

    #[Route('', name: 'store', methods: ['POST'])]
    #[IsGranted('role.create')]
    public function store(#[MapRequestPayload] CreateRoleRequestDto $dto): array
    {
        $role = $this->roleService->create($dto);

        return RoleMapper::toArray($role);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('role.edit')]
    public function update(Role $role, #[MapRequestPayload] UpdateRoleRequestDto $dto): array
    {
        $updated = $this->roleService->update($role, $dto);

        return RoleMapper::toArray($updated);
    }

    #[Route('/{id}', name: 'destroy', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('role.delete')]
    public function destroy(Role $role): Response
    {
        $this->roleService->delete($role);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
