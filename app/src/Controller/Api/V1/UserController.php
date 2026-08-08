<?php

namespace App\Controller\Api\V1;

use App\Dto\User\CreateUserRequestDto;
use App\Dto\User\UpdateUserRequestDto;
use App\Entity\User;
use App\Http\Mapper\UserMapper;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/users', name: 'api_v1_users_')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('user.view')]
    public function index(): array
    {
        return UserMapper::collection($this->userService->list());
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('user.view')]
    public function show(User $user): array
    {
        return UserMapper::toArray($user);
    }

    #[Route('', name: 'store', methods: ['POST'])]
    #[IsGranted('user.create')]
    public function store(#[MapRequestPayload] CreateUserRequestDto $dto): array
    {
        $user = $this->userService->create($dto);

        return UserMapper::toArray($user);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('user.edit')]
    public function update(User $user, #[MapRequestPayload] UpdateUserRequestDto $dto): array
    {
        $updated = $this->userService->update($user, $dto);

        return UserMapper::toArray($updated);
    }

    #[Route('/{id}', name: 'destroy', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('user.delete')]
    public function destroy(User $user): Response
    {
        $this->userService->delete($user);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
