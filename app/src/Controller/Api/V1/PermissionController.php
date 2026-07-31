<?php

namespace App\Controller\Api\V1;

use App\Dto\Permission\PermissionRequestDto;
use App\Entity\Permission;
use App\Http\Mapper\PermissionMapper;
use App\Service\PermissionService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/permissions', name: 'api_v1_permissions_')]
class PermissionController
{
    public function __construct(
        private readonly PermissionService $permissionService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): array
    {
        return PermissionMapper::collection($this->permissionService->list());
    }

    // Type-hint "Permission $permission" (không phải int $id) -> Doctrine's
    // EntityValueResolver (built-in, tự động bật khi cài doctrine/doctrine-bundle)
    // tự query theo route param {id}, và tự throw NotFoundHttpException (404)
    // nếu không tìm thấy -> không cần code tay find() + check null.
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Permission $permission): array
    {
        return PermissionMapper::toArray($permission);
    }

    #[Route('', name: 'store', methods: ['POST'])]
    public function store(#[MapRequestPayload] PermissionRequestDto $dto): array
    {
        $permission = $this->permissionService->create($dto);

        return PermissionMapper::toArray($permission);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(Permission $permission, #[MapRequestPayload] PermissionRequestDto $dto): array
    {
        $updated = $this->permissionService->update($permission, $dto);

        return PermissionMapper::toArray($updated);
    }

    #[Route('/{id}', name: 'destroy', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function destroy(Permission $permission): Response
    {
        $this->permissionService->delete($permission);

        // 204 No Content -> return thẳng Response, ApiResponseListener sẽ
        // KHÔNG can thiệp (vì đã là Response instance, xem lại điều kiện
        // trong ApiResponseListener::__invoke())
        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
