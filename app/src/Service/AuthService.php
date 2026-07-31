<?php

namespace App\Service;

use App\Dto\Auth\LoginRequestDto;
use App\Dto\Auth\LoginResultDto;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\DisabledException;

final class AuthService
{
    private const INVALID_CREDENTIALS_MESSAGE = 'Email or password is incorrect.';

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
        #[Autowire('%lexik_jwt_authentication.token_ttl%')]
        private readonly int $tokenTtl,
    ) {
    }

    /**
     * Xác thực user và trả về JWT token cùng thông tin user.
     *
     * @throws DisabledException       khi tài khoản bị khoá
     * @throws BadRequestHttpException               khi email/password sai
     */
    public function login(LoginRequestDto $dto): LoginResultDto
    {
        $user = $this->userRepository->findOneBy(['email' => $dto->email]);
        if ($user === null) {
            throw new BadRequestHttpException(self::INVALID_CREDENTIALS_MESSAGE);
        }

        if (!$user->isActive()) {
            throw new DisabledException();
        }

        if (!$this->passwordHasher->isPasswordValid($user, $dto->password)) {
            throw new BadRequestHttpException(self::INVALID_CREDENTIALS_MESSAGE);
        }

        $token = $this->jwtManager->create($user);

        return new LoginResultDto(
            token: $token,
            expiresIn: $this->tokenTtl,
            user: $user,
        );
    }
}
