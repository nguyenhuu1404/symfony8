<?php

namespace App\Controller\Api\V1;

use App\Http\Request\Auth\LoginRequest;
use App\Http\Response\ApiResponse;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\DisabledException;

#[Route('/api/v1/auth', name: 'api_v1_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $data = $this->authService->login($request);

            return ApiResponse::success($data, 'Login successful.');
        } catch (BadCredentialsException) {
            return ApiResponse::unauthorized('Invalid credentials.');
        } catch (DisabledException) {
            return ApiResponse::unauthorized('Your account has been deactivated.');
        }
    }
}
