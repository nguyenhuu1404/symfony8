<?php

namespace App\Controller\Api\V1;

use App\Dto\Auth\LoginRequestDto;
use App\Http\Mapper\LoginResultMapper;
use App\Service\AuthService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/auth', name: 'api_v1_auth_')]
class AuthController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(#[MapRequestPayload] LoginRequestDto $dto): array
    {
        $result = $this->authService->login($dto);

        return LoginResultMapper::toArray($result);
    }
}
